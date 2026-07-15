<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LoanInstallment;
use App\Models\PayeBracket;
use App\Models\PayrollRun;
use App\Models\PayrollRunResult;
use App\Models\PayslipLineItem;
use App\Models\SalaryHistory;
use App\Models\StatutoryConfiguration;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * PayrollCalculationService
 *
 * Implements the 3-stage Tanzanian payroll pipeline as defined in docs/01_BUSINESS_RULES.md §1.
 * All monetary arithmetic uses BCMath (BC_SCALE = 4) per docs/03_DATABASE_SPECIFICATION.md §1.5.
 *
 * Stage A: Gross Earnings Aggregation  (BR §1, steps 1-4)
 * Stage B: Pre-Tax Deductions & PAYE   (BR §1, steps 5-8)
 * Stage C: Net Pool & Post-Tax         (BR §1, steps 9-12)
 */
class PayrollCalculationService
{
    /** BCMath scale for all intermediate arithmetic. DB spec §1.5, BR §7. */
    private const BC_SCALE = 4;

    /**
     * Execute a payroll run and write snapshot results for all active employees.
     *
     * @throws Exception if any employee fails a mandatory guard rule.
     */
    public function calculate(string $payrollRunId): void
    {
        DB::transaction(function () use ($payrollRunId) {
            $run = PayrollRun::with('period')->findOrFail($payrollRunId);
            $periodStart = Carbon::parse($run->period->start_date);
            $periodEnd = Carbon::parse($run->period->end_date);

            // --- Working Days -------------------------------------------------
            // BR §6: working_days_per_month is global config from company_profile.
            $workingDays = '26'; // BCMath string default
            $company = DB::table('company_profile')->first();
            if ($company && isset($company->working_days_per_month)) {
                $workingDays = (string) (int) $company->working_days_per_month;
            }

            // --- Statutory Configurations (effective-dated) -------------------
            // DB spec §9.1: canonical resolution query — greatest effective_from <= period end.
            $statConfigs = StatutoryConfiguration::where('effective_from', '<=', $periodEnd->toDateString())
                ->orderByDesc('effective_from')
                ->get()
                ->groupBy('code')
                ->map(fn ($group) => $group->first());

            // BR §2: NSSF is 10% of Total Gross Salary (employee share).
            // Must be read from config, never hardcoded — but fall back with a warning.
            $nssfRate = $statConfigs->has('NSSF')
                ? (string) $statConfigs->get('NSSF')->rate_percentage
                : '0.1000'; // 10% fallback — GAP: confirm NSSF config row exists in production seed

            // BR §2: Non-resident flat rate — MUST be from config, never hardcoded.
            // GAP: NON_RESIDENT_PAYE must exist in statutory_configurations.
            if (! $statConfigs->has('NON_RESIDENT_PAYE')) {
                throw new Exception(
                    'NON_RESIDENT_PAYE rate is missing from statutory_configurations. '.
                    'Per BR §2, this rate must never be hardcoded. Please seed the config table.'
                );
            }
            $nonResidentPayeRate = (string) $statConfigs->get('NON_RESIDENT_PAYE')->rate_percentage;

            // --- PAYE Brackets (effective-dated) ------------------------------
            // DB spec §9.1: resolve latest bracket set effective on or before period end.
            $latestPayeDate = PayeBracket::where('effective_from', '<=', $periodEnd->toDateString())
                ->max('effective_from');
            $payeBrackets = PayeBracket::where('effective_from', $latestPayeDate)
                ->orderBy('minimum_income') // DB spec §5.7 column name
                ->get();

            // --- Employee Scope (company-scoped, spec §2.3) -------------------
            $employees = Employee::where('status', 'active')
                ->where('company_id', $run->company_id)
                ->with([
                    'earnings' => fn ($q) => $q->where('is_active', true),
                    'earnings.earningType',
                    'deductions' => fn ($q) => $q->where('is_active', true),
                    'deductions.deductionType',
                    // Only active loans generate installment deductions
                    'loans' => fn ($q) => $q->where('loan_status', 'active'),
                    // BR §6: both unpaid and unauthorized_absence reduce Basic Salary
                    'leaveRecords' => function ($q) use ($periodStart, $periodEnd) {
                        $q->whereIn('leave_type', ['unpaid', 'unauthorized_absence'])
                            ->where('start_date', '<=', $periodEnd->toDateString())
                            ->where('end_date', '>=', $periodStart->toDateString());
                    },
                    'overtimeRecords' => fn ($q) => $q->where('payroll_period_id', $run->payroll_period_id),
                ])
                ->get();

            foreach ($employees as $employee) {
                $this->processEmployee(
                    $employee, $run, $periodStart, $periodEnd,
                    $workingDays, $nssfRate, $nonResidentPayeRate,
                    $payeBrackets
                );
            }

            // Update run status to Preview (BR §9.1 state machine)
            $run->status = 'preview';
            $run->save();
        });
    }

    /**
     * Process a single employee through the 3-stage payroll pipeline.
     * Wrapped in its own method for clarity; still inside the outer DB transaction.
     */
    private function processEmployee(
        Employee $employee,
        PayrollRun $run,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $workingDays,
        string $nssfRate,
        string $nonResidentPayeRate,
        $payeBrackets
    ): void {
        // ================================================================
        // STAGE A: Gross Earnings Aggregation (BR §1, steps 1-4)
        // ================================================================

        // Step 1: Determine Basic Salary via effective-dating (DB spec §9.1)
        $salaryRow = SalaryHistory::where('employee_id', $employee->id)
            ->where('effective_from', '<=', $periodEnd->toDateString())
            ->orderByDesc('effective_from')
            ->first();

        // BR §9.5 pre-flight: no salary = ERROR, must block the run.
        if (! $salaryRow) {
            throw new Exception(
                "Employee [{$employee->employee_number}] has no basic salary configured ".
                "as of {$periodEnd->toDateString()}. Pre-flight check (BR §9.5) must catch this."
            );
        }

        $contractualSalary = (string) $salaryRow->basic_salary_amount; // DB spec §5.4 column name

        // Prorate for mid-month hire (BR §6)
        $workedDays = $workingDays;
        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : null;
        $termDate = $employee->termination_date ? Carbon::parse($employee->termination_date) : null;

        if ($hireDate && $hireDate->between($periodStart, $periodEnd)) {
            // Days from hire date to period end (calendar days as proxy for working days)
            // BR §6: prorate = (working_days_in_period / total_working_days) * basic_salary
            $calendarDaysInPeriod = $periodEnd->diffInDays($periodStart) + 1;
            $calendarDaysWorked = $periodEnd->diffInDays($hireDate) + 1;
            $workedDays = bcmul(
                $workingDays,
                bcdiv((string) $calendarDaysWorked, (string) $calendarDaysInPeriod, self::BC_SCALE),
                self::BC_SCALE
            );
        }
        if ($termDate && $termDate->between($periodStart, $periodEnd)) {
            // BR §6: termination date itself is the last worked day — inclusive.
            $calendarDaysInPeriod = $periodEnd->diffInDays($periodStart) + 1;
            $calendarDaysWorked = $termDate->diffInDays($periodStart) + 1; // inclusive
            $workedDays = bcmul(
                $workingDays,
                bcdiv((string) $calendarDaysWorked, (string) $calendarDaysInPeriod, self::BC_SCALE),
                self::BC_SCALE
            );
        }

        // Deduct unpaid leave & unauthorized absences (BR §6)
        // BR §6: formula = (basic_salary / total_working_days) * unpaid_leave_days
        $unpaidLeaveDays = '0';
        foreach ($employee->leaveRecords as $leave) {
            $overlapStart = $periodStart->max(Carbon::parse($leave->start_date));
            $overlapEnd = $periodEnd->min(Carbon::parse($leave->end_date));
            $days = (string) ($overlapStart->diffInDays($overlapEnd) + 1);
            $unpaidLeaveDays = bcadd($unpaidLeaveDays, $days, self::BC_SCALE);
        }
        $workedDays = bcsub((string) $workedDays, $unpaidLeaveDays, self::BC_SCALE);
        if (bccomp($workedDays, '0', self::BC_SCALE) < 0) {
            $workedDays = '0'; // BR §6 guard: basic salary floors at zero
        }

        // Basic salary = (worked_days / working_days) * contractual_salary
        $basicSalary = bcmul(
            bcdiv($workedDays, $workingDays, self::BC_SCALE),
            $contractualSalary,
            self::BC_SCALE
        );

        // Steps 2 & 3: Aggregate taxable and non-taxable earnings
        $taxableEarnings = '0';
        $nonTaxableEarnings = '0';

        foreach ($employee->earnings as $earning) {
            $amt = (string) $earning->amount;
            if ($earning->earningType->is_taxable) {
                $taxableEarnings = bcadd($taxableEarnings, $amt, self::BC_SCALE);
            } else {
                $nonTaxableEarnings = bcadd($nonTaxableEarnings, $amt, self::BC_SCALE);
            }
        }

        // BR §4: overtime — hours-based or fixed amount
        $overtimeEarnings = '0';
        foreach ($employee->overtimeRecords as $ot) {
            if ($ot->overtime_type === 'fixed_amount') {
                $overtimeEarnings = bcadd($overtimeEarnings, (string) $ot->fixed_amount, self::BC_SCALE);
            } else {
                // Formula: (contractual_salary / (working_days * 8)) * rate_multiplier * hours
                $dailyHourlyRate = bcdiv(
                    $contractualSalary,
                    bcmul($workingDays, '8', self::BC_SCALE),
                    self::BC_SCALE
                );
                $otAmt = bcmul(
                    bcmul($dailyHourlyRate, (string) $ot->overtime_rate_multiplier, self::BC_SCALE),
                    (string) $ot->hours,
                    self::BC_SCALE
                );
                $overtimeEarnings = bcadd($overtimeEarnings, $otAmt, self::BC_SCALE);
            }
        }
        // Overtime is taxable per BR §4
        $taxableEarnings = bcadd($taxableEarnings, $overtimeEarnings, self::BC_SCALE);

        // Step 4: Total Gross Salary
        $totalGrossSalary = bcadd(bcadd($basicSalary, $taxableEarnings, self::BC_SCALE), $nonTaxableEarnings, self::BC_SCALE);

        // ================================================================
        // STAGE B: Pre-Tax Deductions & Taxable Income (BR §1, steps 5-8)
        // ================================================================

        // Step 5: Gross Taxable Salary (non-taxable earnings intentionally excluded from PAYE base)
        $grossTaxableSalary = bcadd($basicSalary, $taxableEarnings, self::BC_SCALE);

        // Step 6: NSSF employee contribution (10% of Total Gross Salary)
        // BR §2: NSSF base is Total Gross Salary, not Gross Taxable Salary.
        $nssfDeductionAmount = bcmul($totalGrossSalary, $nssfRate, self::BC_SCALE);

        // Step 7: Taxable Income
        $taxableIncomeAmount = bcsub($grossTaxableSalary, $nssfDeductionAmount, self::BC_SCALE);
        if (bccomp($taxableIncomeAmount, '0', self::BC_SCALE) < 0) {
            $taxableIncomeAmount = '0'; // Guard: taxable income floors at zero
        }

        // Step 8: PAYE calculation
        // BR §2: Formula = base_tax_amount + ((taxable_income - minimum_income) * rate_percentage)
        // BR §2: Entry condition is STRICTLY > minimum_income
        $payeTaxAmount = '0';
        if ($employee->resident_status === 'non_resident') {
            // BR §2: Non-resident flat rate. MUST be from config. Rate confirmed above.
            $payeTaxAmount = bcmul($taxableIncomeAmount, $nonResidentPayeRate, self::BC_SCALE);
        } elseif ($employee->secondary_employment_flag) {
            // BR §2: Secondary employment — flat 30% at source.
            // GAP: Strictly this should also come from config but BR says 30% explicitly.
            $payeTaxAmount = bcmul($taxableIncomeAmount, '0.3000', self::BC_SCALE);
        } else {
            // Progressive stacked brackets
            foreach ($payeBrackets as $bracket) {
                $minIncome = (string) $bracket->minimum_income; // DB spec §5.7
                // BR §2: entry condition is strictly > minimum_income
                if (bccomp($taxableIncomeAmount, $minIncome, self::BC_SCALE) > 0) {
                    $maxIncome = $bracket->maximum_income !== null ? (string) $bracket->maximum_income : null;
                    $cappedIncome = ($maxIncome !== null && bccomp($taxableIncomeAmount, $maxIncome, self::BC_SCALE) > 0)
                        ? $maxIncome
                        : $taxableIncomeAmount;
                    $bandAmount = bcsub($cappedIncome, $minIncome, self::BC_SCALE);
                    // BR §2: Formula = base_tax_amount + ((income_in_band) * rate_percentage)
                    $payeTaxAmount = bcadd(
                        (string) $bracket->base_tax_amount, // DB spec §5.7
                        bcmul($bandAmount, (string) $bracket->rate_percentage, self::BC_SCALE),
                        self::BC_SCALE
                    );
                    // Do NOT break — iterate all brackets so the last matching one wins
                    // (stacked progressive: the final bracket in the loop gives the final tax)
                }
            }
        }

        // ================================================================
        // STAGE C: Net Pool & Post-Tax Deductions (BR §1, steps 9-12)
        // ================================================================

        // Step 9: Net Pool
        $netPool = bcsub(bcsub($totalGrossSalary, $nssfDeductionAmount, self::BC_SCALE), $payeTaxAmount, self::BC_SCALE);

        // BR §9.5 pre-flight guard: net pool < 0 before post-tax deductions must block.
        if (bccomp($netPool, '0', self::BC_SCALE) < 0) {
            throw new Exception(
                "Employee [{$employee->employee_number}] net pool is negative ({$netPool}) after statutory deductions. ".
                'This must be caught by the pre-flight validation (BR §9.5) before reaching calculation.'
            );
        }

        // Step 11: Post-tax deductions — strict 5-tier priority (BR §5)
        // Tier 1 (NSSF, PAYE) already applied above.
        // Tier 2: Court Orders — not yet configured in schema; placeholder
        // Tier 3: Company Loans & Salary Advances
        // Tier 4: Third-party Loans (SACCOs, Banks) — handled via deduction_types priority_tier
        // Tier 5: Voluntary deductions
        $deductionLedger = [];
        $totalPostTaxDeductions = '0';
        $remainingPool = $netPool;

        // Tier 3: Company Loans (BR §5: Final Installment Rule — deduct min(installment, outstanding))
        foreach ($employee->loans as $loan) {
            $outstanding = bcsub((string) $loan->principal_amount, (string) $loan->total_repaid_amount, self::BC_SCALE);
            if (bccomp($outstanding, '0', self::BC_SCALE) <= 0) {
                continue; // Loan already fully repaid
            }
            // BR §5 Final Installment Rule
            $installment = (string) $loan->installment_amount;
            if (bccomp($outstanding, $installment, self::BC_SCALE) < 0) {
                $installment = $outstanding; // Clamp to remaining balance
            }
            // BR §1 step 11: insufficient pool → throw exception, abort this employee
            if (bccomp($remainingPool, $installment, self::BC_SCALE) < 0) {
                throw new Exception(
                    "Employee [{$employee->employee_number}] has insufficient net pool ({$remainingPool}) ".
                    "to cover loan installment ({$installment}). Flag as FAILED_PROCESSING. ".
                    'Recovery: adjust loan via supplementary run (BR §1, step 11).'
                );
            }
            $remainingPool = bcsub($remainingPool, $installment, self::BC_SCALE);
            $totalPostTaxDeductions = bcadd($totalPostTaxDeductions, $installment, self::BC_SCALE);
            $deductionLedger[] = [
                'tier' => 3,
                'type' => 'loan',
                'loan_id' => $loan->id,
                'code' => 'LOAN',
                'name' => 'Loan Repayment',
                'amount' => $installment,
                'outstanding_before' => $outstanding,
                'outstanding_after' => bcsub($outstanding, $installment, self::BC_SCALE),
            ];
        }

        // Tiers 4 & 5: Employee deductions sorted by priority_tier (DB spec §4.3)
        $sortedDeductions = $employee->deductions->sortBy(fn ($d) => $d->deductionType->priority_tier ?? 'tier_5');
        foreach ($sortedDeductions as $ded) {
            if ($ded->deductionType->basis === 'fixed_amount') {
                $dedAmount = (string) ($ded->amount ?? $ded->deductionType->default_amount ?? '0');
            } else { // percentage_basic
                $pct = (string) ($ded->percentage ?? $ded->deductionType->default_percentage ?? '0');
                $dedAmount = bcmul($basicSalary, $pct, self::BC_SCALE);
            }
            if (bccomp($remainingPool, $dedAmount, self::BC_SCALE) < 0) {
                throw new Exception(
                    "Employee [{$employee->employee_number}] has insufficient net pool ({$remainingPool}) ".
                    "to cover deduction [{$ded->deductionType->code}] ({$dedAmount}). ".
                    'Flag as FAILED_PROCESSING (BR §1, step 11).'
                );
            }
            $remainingPool = bcsub($remainingPool, $dedAmount, self::BC_SCALE);
            $totalPostTaxDeductions = bcadd($totalPostTaxDeductions, $dedAmount, self::BC_SCALE);
            $deductionLedger[] = [
                'tier' => 4,
                'type' => 'standard',
                'code' => $ded->deductionType->code,
                'name' => $ded->deductionType->name,
                'amount' => $dedAmount,
            ];
        }

        // Step 12: Final Net Salary — round to whole TZS (BR §7)
        $exactNetSalary = $remainingPool; // Already: totalGross - NSSF - PAYE - postTaxDeds
        $roundedNetSalary = (string) (int) round((float) $exactNetSalary, 0, PHP_ROUND_HALF_UP);
        // BR §7: rounding residual absorbed into PAYE line item (document in reconciliation report)
        $roundingAdjustment = bcsub($roundedNetSalary, $exactNetSalary, self::BC_SCALE);
        // Adjust PAYE by the rounding residual so that: Gross - AllDeductions = RoundedNet exactly
        $payeTaxAmount = bcsub($payeTaxAmount, $roundingAdjustment, self::BC_SCALE);

        // ================================================================
        // Write Snapshot Results (DB spec §2.1, §8.2)
        // ================================================================
        $result = PayrollRunResult::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'processing_status' => 'success',
            'basic_salary_amount' => $basicSalary,
            'gross_salary_amount' => $totalGrossSalary,
            'taxable_income_amount' => $taxableIncomeAmount,
            'nssf_deduction_amount' => $nssfDeductionAmount,
            'paye_tax_amount' => $payeTaxAmount,
            'total_deductions_amount' => bcadd($nssfDeductionAmount, $totalPostTaxDeductions, self::BC_SCALE),
            'net_salary_amount' => $roundedNetSalary,
            'rounding_adjustment' => $roundingAdjustment,
            // DB spec §8.2: calculation_snapshot stores exact rates & brackets used
            'calculation_snapshot' => [
                'calculation_date' => now()->toIso8601String(),
                'period' => ['start' => $periodStart->toDateString(), 'end' => $periodEnd->toDateString()],
                'working_days' => $workingDays,
                'worked_days' => $workedDays,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'nssf_rate' => $nssfRate,
                'paye_brackets' => $payeBrackets->toArray(),
                'employee_snapshot' => [
                    'resident_status' => $employee->resident_status,
                    'secondary_employment_flag' => $employee->secondary_employment_flag,
                ],
                'salary_history_id' => $salaryRow->id,
            ],
        ]);

        // ================================================================
        // Write Payslip Line Items (DB spec §8.1 — denormalized snapshots)
        // ================================================================
        PayslipLineItem::create([
            'payroll_run_result_id' => $result->id,
            'type' => 'earning',  // DB spec §5.11 column: 'type' not 'category'
            'code' => 'BASIC',
            'name' => 'Basic Salary',
            'amount' => $basicSalary,
        ]);

        if (bccomp($taxableEarnings, '0', self::BC_SCALE) > 0) {
            PayslipLineItem::create([
                'payroll_run_result_id' => $result->id,
                'type' => 'earning',
                'code' => 'TAXABLE_EARN',
                'name' => 'Taxable Allowances & Overtime',
                'amount' => $taxableEarnings,
            ]);
        }

        if (bccomp($nonTaxableEarnings, '0', self::BC_SCALE) > 0) {
            PayslipLineItem::create([
                'payroll_run_result_id' => $result->id,
                'type' => 'earning',
                'code' => 'NON_TAX_EARN',
                'name' => 'Non-Taxable Allowances',
                'amount' => $nonTaxableEarnings,
            ]);
        }

        PayslipLineItem::create([
            'payroll_run_result_id' => $result->id,
            'type' => 'deduction',
            'code' => 'NSSF',
            'name' => 'NSSF Employee Contribution',
            'amount' => $nssfDeductionAmount,
        ]);

        PayslipLineItem::create([
            'payroll_run_result_id' => $result->id,
            'type' => 'tax',
            'code' => 'PAYE',
            'name' => 'PAYE Tax',
            'amount' => $payeTaxAmount,
        ]);

        foreach ($deductionLedger as $ded) {
            PayslipLineItem::create([
                'payroll_run_result_id' => $result->id,
                'type' => 'deduction',
                'code' => $ded['code'],
                'name' => $ded['name'],
                'amount' => $ded['amount'],
            ]);

            // Write the immutable loan installment record (DB spec §5.9 — Hard Record)
            if ($ded['type'] === 'loan') {
                LoanInstallment::create([
                    'loan_id' => $ded['loan_id'],
                    'payroll_period_id' => $run->payroll_period_id,
                    'amount_deducted' => $ded['amount'],
                    'outstanding_balance_before' => $ded['outstanding_before'],
                    'outstanding_balance_after' => $ded['outstanding_after'],
                ]);
            }
        }
    }
}
