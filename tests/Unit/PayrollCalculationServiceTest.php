<?php

namespace Tests\Unit;

use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PayrollCalculationServiceTest
 *
 * Validates the 3-stage Tanzanian payroll pipeline against known inputs from
 * docs/01_BUSINESS_RULES.md §1 and the 2025/26 PAYE bracket example in BR §2.
 *
 * Manual calculation for a resident employee earning 1,000,000 TZS basic:
 *   Gross Taxable Salary = 1,000,000 (no allowances)
 *   Total Gross Salary   = 1,000,000
 *   NSSF (10%)           = 100,000
 *   Taxable Income       = 1,000,000 - 100,000 = 900,000
 *   PAYE (bracket: > 760,000 – 1,000,000 → base 68,000 + 25% of (900,000 - 760,000)):
 *     = 68,000 + 0.25 * 140,000 = 68,000 + 35,000 = 103,000
 *   Net Pool             = 1,000,000 - 100,000 - 103,000 = 797,000
 *   Net Salary (rounded) = 797,000  (no fractional residual here)
 *   Rounding Adjustment  = 0
 */
class PayrollCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $companyId;

    private string $periodId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyId = (string) Str::uuid();

        // Seed company profile (working_days_per_month)
        \DB::table('company_profile')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->companyId,
            'company_name' => 'Test Co Ltd',
            'working_days_per_month' => 26,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2025/26 PAYE Brackets — exact values from BR §2 example
        // DB spec §5.7 column names: minimum_income, maximum_income, base_tax_amount
        \DB::table('paye_brackets')->insert([
            ['id' => Str::uuid(), 'effective_from' => '2025-07-01', 'minimum_income' => 0,       'maximum_income' => 270000,  'rate_percentage' => 0,    'base_tax_amount' => 0],
            ['id' => Str::uuid(), 'effective_from' => '2025-07-01', 'minimum_income' => 270000,  'maximum_income' => 520000,  'rate_percentage' => 0.08, 'base_tax_amount' => 0],
            ['id' => Str::uuid(), 'effective_from' => '2025-07-01', 'minimum_income' => 520000,  'maximum_income' => 760000,  'rate_percentage' => 0.20, 'base_tax_amount' => 20000],
            ['id' => Str::uuid(), 'effective_from' => '2025-07-01', 'minimum_income' => 760000,  'maximum_income' => 1000000, 'rate_percentage' => 0.25, 'base_tax_amount' => 68000],
            ['id' => Str::uuid(), 'effective_from' => '2025-07-01', 'minimum_income' => 1000000, 'maximum_income' => null,    'rate_percentage' => 0.30, 'base_tax_amount' => 128000],
        ]);

        // Statutory configurations — NSSF and NON_RESIDENT_PAYE (mandatory per BR §2)
        \DB::table('statutory_configurations')->insert([
            ['id' => Str::uuid(), 'code' => 'NSSF',              'name' => 'NSSF Employee', 'rate_percentage' => 0.10, 'effective_from' => '2025-07-01', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid(), 'code' => 'NON_RESIDENT_PAYE', 'name' => 'Non-Resident PAYE Rate', 'rate_percentage' => 0.15, 'effective_from' => '2025-07-01', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Payroll period
        $this->periodId = (string) Str::uuid();
        \DB::table('payroll_periods')->insert([
            'id' => $this->periodId,
            'company_id' => $this->companyId,
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Creates a payroll run and returns its ID */
    private function createRun(): string
    {
        $runId = (string) Str::uuid();
        \DB::table('payroll_runs')->insert([
            'id' => $runId,
            'payroll_period_id' => $this->periodId,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $runId;
    }

    /** Creates an employee and returns its ID */
    private function createEmployee(array $overrides = []): string
    {
        $empId = (string) Str::uuid();
        \DB::table('employees')->insert(array_merge([
            'id' => $empId,
            'company_id' => $this->companyId,
            'employee_number' => 'EMP-'.rand(100, 999),
            'first_name' => 'Test',
            'last_name' => 'User',
            'status' => 'active',
            'resident_status' => 'resident',
            'secondary_employment_flag' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return $empId;
    }

    /** Seeds a salary history row using the DB spec §5.4 column name */
    private function seedSalary(string $employeeId, float $amount): void
    {
        \DB::table('salary_histories')->insert([
            'id' => (string) Str::uuid(),
            'employee_id' => $employeeId,
            'basic_salary_amount' => $amount, // DB spec §5.4: NOT 'salary'
            'effective_from' => '2026-07-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test 1: Standard resident employee, no allowances, no loans.
     * Expected PAYE: 103,000 | Net: 797,000
     */
    public function test_resident_employee_standard_paye(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee();
        $this->seedSalary($empId, 1_000_000);

        (new PayrollCalculationService)->calculate($runId);

        // DB spec §5.11 column names
        $this->assertDatabaseHas('payroll_run_results', [
            'payroll_run_id' => $runId,
            'employee_id' => $empId,
            'processing_status' => 'success',
            'basic_salary_amount' => '1000000.0000',
            'gross_salary_amount' => '1000000.0000',
            'taxable_income_amount' => '900000.0000',   // 1M - 100K NSSF
            'nssf_deduction_amount' => '100000.0000',
            'paye_tax_amount' => '103000.0000',   // 68000 + 0.25*(900000-760000)
            'net_salary_amount' => '797000.0000',   // 1M - 100K - 103K
            'rounding_adjustment' => '0.0000',
        ]);

        // Payslip line items — DB spec §5.11 column names: 'type' and 'name'
        $this->assertDatabaseHas('payslip_line_items', [
            'code' => 'BASIC',
            'type' => 'earning',        // DB spec: 'type', not 'category'
            'name' => 'Basic Salary',   // DB spec: 'name', not 'description'
            'amount' => '1000000.0000',
        ]);
        $this->assertDatabaseHas('payslip_line_items', [
            'code' => 'NSSF',
            'type' => 'deduction',
            'amount' => '100000.0000',
        ]);
        $this->assertDatabaseHas('payslip_line_items', [
            'code' => 'PAYE',
            'type' => 'tax',
            'amount' => '103000.0000',
        ]);

        // Run status transitions to preview (BR §9.1 state machine)
        $this->assertDatabaseHas('payroll_runs', ['id' => $runId, 'status' => 'preview']);
    }

    /**
     * Test 2: Employee below PAYE threshold (520,000 TZS basic).
     * Taxable income after NSSF = 520000 - 52000 = 468,000 → bracket: 270K–520K → 8% of (468K-270K) = 15,840
     */
    public function test_employee_below_top_paye_bracket(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee(['employee_number' => 'EMP-200']);
        $this->seedSalary($empId, 520_000);

        (new PayrollCalculationService)->calculate($runId);

        // NSSF = 52,000 | Taxable = 468,000 | PAYE = 0 + 8% * (468000-270000) = 15,840
        $this->assertDatabaseHas('payroll_run_results', [
            'employee_id' => $empId,
            'nssf_deduction_amount' => '52000.0000',
            'taxable_income_amount' => '468000.0000',
            'paye_tax_amount' => '15840.0000',
            'net_salary_amount' => '452160.0000', // 520000 - 52000 - 15840
        ]);
    }

    /**
     * Test 3: Employee in zero-tax bracket (income <= 270,000 after NSSF).
     * Basic = 270,000 | NSSF = 27,000 | Taxable = 243,000 → 0% → PAYE = 0
     */
    public function test_employee_in_zero_tax_bracket(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee(['employee_number' => 'EMP-300']);
        $this->seedSalary($empId, 270_000);

        (new PayrollCalculationService)->calculate($runId);

        $this->assertDatabaseHas('payroll_run_results', [
            'employee_id' => $empId,
            'paye_tax_amount' => '0.0000',
        ]);
    }

    /**
     * Test 4: Non-resident employee uses the flat rate from statutory_configurations (15%).
     */
    public function test_non_resident_employee_flat_paye(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee(['employee_number' => 'EMP-400', 'resident_status' => 'non_resident']);
        $this->seedSalary($empId, 1_000_000);

        (new PayrollCalculationService)->calculate($runId);

        // NSSF = 100,000 | Taxable = 900,000 | PAYE (15% flat) = 135,000
        $this->assertDatabaseHas('payroll_run_results', [
            'employee_id' => $empId,
            'paye_tax_amount' => '135000.0000',
        ]);
    }

    /**
     * Test 5: Secondary employment employee — flat 30% rate.
     */
    public function test_secondary_employment_flat_paye(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee([
            'employee_number' => 'EMP-500',
            'secondary_employment_flag' => true,
        ]);
        $this->seedSalary($empId, 1_000_000);

        (new PayrollCalculationService)->calculate($runId);

        // Taxable = 900,000 | PAYE = 900,000 * 0.30 = 270,000
        $this->assertDatabaseHas('payroll_run_results', [
            'employee_id' => $empId,
            'paye_tax_amount' => '270000.0000',
        ]);
    }

    /**
     * Test 6: Unpaid leave reduces basic salary proportionally.
     * Basic = 1,000,000 | Working days = 26 | Unpaid leave = 4 days
     * Worked days = 26 - 4 = 22
     * Basic after deduction = (22/26) * 1,000,000 = 846,153.8462
     */
    public function test_unpaid_leave_reduces_basic_salary(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee(['employee_number' => 'EMP-600']);
        $this->seedSalary($empId, 1_000_000);

        // Seed 4 days of unpaid leave in July 2026
        \DB::table('leave_records')->insert([
            'id' => (string) Str::uuid(),
            'employee_id' => $empId,
            'leave_type' => 'unpaid',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-04',
            'total_days' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new PayrollCalculationService)->calculate($runId);

        // BCMath truncates at scale 4 (does not round intermediate results):
        // 22 / 26 = 0.8461 (truncated at 4dp), NOT 0.8462 (which would be rounding)
        // 0.8461 * 1,000,000 = 846,100.0000
        // Per BR §7: only the final net salary uses half-up rounding. Intermediates truncate.
        $this->assertDatabaseHas('payroll_run_results', [
            'employee_id' => $empId,
            'basic_salary_amount' => '846100.0000', // BCMath bcdiv('22','26',4) = 0.8461 (truncation)
        ]);
    }

    /**
     * Test 7: Loan final installment rule — deducts only outstanding balance, not full installment.
     * BR §5: If outstanding_balance < installment_amount, deduct only outstanding.
     */
    public function test_loan_final_installment_rule(): void
    {
        $runId = $this->createRun();
        $empId = $this->createEmployee(['employee_number' => 'EMP-700']);
        $this->seedSalary($empId, 1_000_000);

        // Loan: principal 1,000,000, installment 100,000, total_repaid 950,000 → outstanding = 50,000
        $loanId = (string) Str::uuid();
        \DB::table('loans')->insert([
            'id' => $loanId,
            'employee_id' => $empId,
            'principal_amount' => 1_000_000,
            'installment_amount' => 100_000,
            'total_repaid_amount' => 950_000,
            'loan_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new PayrollCalculationService)->calculate($runId);

        // Final installment should be 50,000 (not 100,000)
        $this->assertDatabaseHas('payslip_line_items', [
            'code' => 'LOAN',
            'amount' => '50000.0000',
        ]);
        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loanId,
            'amount_deducted' => '50000.0000',
            'outstanding_balance_before' => '50000.0000',
            'outstanding_balance_after' => '0.0000',
        ]);
    }
}
