# 01_BUSINESS_RULES.md - Payroll Business Rules (Tanzania)

## 1. Payroll Calculation Order (CRITICAL)

The payroll processing engine must strictly execute calculations in the following sequence to ensure accurate tax and statutory compliance:

**Stage A: Gross Earnings Aggregation**
1. **Determine Basic Salary**: Prorate based on mid-month hire/termination or Unpaid Leave. *(Guard Rule: Basic Salary floors at zero. It cannot become negative due to excessive unpaid leave data entry errors).*
2. **Calculate Taxable Earnings**: Sum all taxable allowances, overtime, bonuses, commissions, and incentives.
3. **Calculate Non-Taxable Earnings**: Sum all explicitly configured non-taxable allowances.
4. **Determine Total Gross Salary**: `Basic Salary + Taxable Earnings + Non-Taxable Earnings`.

**Stage B: Pre-Tax Deductions & Taxable Income**
5. **Determine Gross Taxable Salary**: `Basic Salary + Taxable Earnings`. *(Design Rule: Non-taxable earnings are intentionally excluded from this figure so they definitively bypass the PAYE tax calculation).*
6. **Calculate Employee Statutory Deductions (Pre-Tax)**:
   - Calculate NSSF (or other approved pension scheme) employee contribution. 
   - **Crucial Base Rule**: Under the NSSF Act, the base is total gross emoluments. Therefore, NSSF is calculated on **Total Gross Salary** (Configurable, typically 10%).
7. **Calculate Taxable Income**: `Gross Taxable Salary - Employee Statutory Deductions (Pre-Tax)`.
8. **Calculate PAYE**: Apply progressive stacked tax brackets to the Taxable Income (even if the resulting tax is zero).

**Stage C: Net Pool & Post-Tax Deductions**
9. **Calculate Net Pool**: `Total Gross Salary - Employee Statutory Deductions - PAYE`.
10. **Calculate Employer Statutory Contributions (Company Liabilities - does not affect net pay)**:
   - NSSF Employer Contribution (Configurable, typically 10% of Total Gross Salary).
   - SDL (Configurable, typically 3.5% of Total Gross Salary, threshold 10+ employees).
   - WCF (Configurable, typically 0.5% of Total Gross Salary for private sector).
11. **Apply Post-Tax Deductions (Strict 5-Tier Priority against the Net Pool)**:
    - Must follow the exact 5-tier priority defined in Section 5 (1. Statutory, 2. Court Orders, 3. Company Loans, 4. Third-Party Loans, 5. Voluntary).
    - *Preview Rule:* If the net pool is insufficient to cover a scheduled deduction, the system must handle this at the **PREVIEW stage** by either **BLOCKING** payroll approval OR **ALLOWING** an override with an admin role.
    - *Runtime Rule:* If a deduction failure bypasses preview and occurs at runtime processing, the engine must throw an exception, abort the transaction for that specific employee, roll back their payslip generation, and flag them as `FAILED_PROCESSING`.
    - *Recovery Workflow:* HR must review the `FAILED_PROCESSING` flag, correct the underlying data constraint (e.g., adjust the loan deduction), and explicitly re-queue the affected employee in a supplementary run. (A supplementary run is an off-cycle payroll run opened within the same period to process a subset of employees).
12. **Calculate Final Net Salary**: `Net Pool - Post-Tax Deductions`.

---

## 2. Tanzania Statutory Payroll Rules

*IMPORTANT: All tax bands, contribution percentages, limits, and thresholds must be stored in configurable database tables with effective dates. Confirm against the latest Finance Act/TRA gazette before locking production defaults. Do NOT hardcode the values below; they represent the structural logic required.*

- **PAYE (Pay As You Earn)**:
  - System must support progressive stacked brackets defined by `Min Amount` (Band Minimum), `Max Amount`, `Fixed Tax Amount`, and `Percentage Over Minimum`.
  - **Crucial Calculation Rule**: The database must store the exact floor for the `Min Amount` (e.g., 270,000) and the entry condition must be `> Min Amount`. The formula uses `(Taxable Income - Band Minimum)`. Storing 270,001 instead of 270,000 as the minimum creates a 1-TZS calculation error.
  - Example structure (2025/26):
    - 0 – 270,000 TZS → 0% (Fixed: 0)
    - > 270,000 – 520,000 TZS → 8% (Fixed: 0, Band Minimum: 270,000)
    - > 520,000 – 760,000 TZS → 20% (Fixed: 20,000, Band Minimum: 520,000)
    - > 760,000 – 1,000,000 TZS → 25% (Fixed: 68,000, Band Minimum: 760,000)
    - > 1,000,000 TZS → 30% (Fixed: 128,000, Band Minimum: 1,000,000)
  - Formula logic: `Fixed Tax Amount + ((Taxable Income - Band Minimum) * Band Percentage)`
  - **Secondary Employment**: Employees flagged as having secondary employment are taxed at a flat rate of 30% at source, rather than through progressive bands.
  - **Non-Residents**: Must have a distinct flag on the Employee profile, as non-residents are subject to different flat tax rates. **The system must enforce a rate lookup from a config table, never a hardcoded default.** (Always verify against the current TRA gazette before setting the production default rate).
- **NSSF (National Social Security Fund)**:
  - Total contribution logic is typically 20% of Total Gross Salary.
  - Split: 10% Employee Deduction (Pre-tax), 10% Employer Contribution.
  - Configurable per employee if they participate in a different approved scheme (e.g., PSSSF for parastatals).
- **SDL (Skills Development Levy)**:
  - Paid entirely by the employer.
  - Calculated as a configurable percentage (e.g., 3.5%) of Total Gross Salary.
  - **Headcount Boundary Rule**: The threshold must be evaluated dynamically per payroll period based on the number of active employees. *Note: One TRA-linked auditor cites 4+ employees, while mainstream guidance says 10+. This threshold must be verified against the latest TRA gazette before building the headcount check logic.* If the active headcount drops below the configured threshold, SDL drops to 0 unless company policy mandates continuous contribution.
  - Must be toggleable at the Company/Branch level for institutional exemptions (e.g., NGOs, schools).
- **WCF (Workers Compensation Fund)**:
  - Paid entirely by the employer.
  - **Industry Variation**: Rates vary by industry risk profile (e.g., typically 0.5% for standard private sector, but may vary for mining, construction, or public sector). The system must support WCF rate configuration at the Company or Branch level.
- **Pension Schemes (Alternative)**:
  - Support for custom pension funds with their own configurable employee/employer percentages.

---

## 3. Employee Participation Logic

- **Explicit Enrollment Required**: Enrollment must be explicitly configured per employee or driven by company policy. No automatic assumption should be made by the system (due to NGOs, contractors, foreign employees, and specific exemptions).
- **Exemptions & Opt-outs**: 
  - Employees must have boolean flags or relationship records to indicate participation in statutory schemes.
  - If an employee is explicitly marked as exempt from NSSF/Pension, their Gross Taxable Salary is subject to full PAYE without any pre-tax pension relief.
- **Switching Schemes**:
  - Employee scheme changes must track the `effective_date`. Payroll processing uses the active scheme configuration as of the payroll period end date.
- **Employer Level Exemptions**:
  - SDL and WCF must be globally toggleable at the Company or Branch level.
- **Partial Contributions**:
  - Optional or private schemes must support both percentage-based deductions and fixed-amount deductions.

---

## 4. Earnings Rules

- **Basic Salary**: The fixed contractual monthly amount.
- **Allowances**: 
  - Allowances **must default to taxable** unless explicitly classified as non-taxable by statutory rule or company policy. Misclassification leads to severe tax compliance issues.
  - Taxable allowances increase Gross Taxable Salary. 
  - Non-taxable allowances bypass PAYE but are definitively included in the Total Gross Salary (forming the base for NSSF calculation) and added to form the Net Pool.
- **Overtime**: 
  - Calculated based on manual HR input.
  - If inputted as hours, formula = `(Basic Salary / Standard Monthly Hours) * Overtime Rate Multiplier * Overtime Hours`.
  - The `Overtime Rate Multiplier` is a configurable decimal stored per `overtime_records.overtime_rate_multiplier`. It must not be hardcoded. Typical values per Tanzania Employment and Labour Relations Act (ELRA) guidance are:
    - **1.5** — standard overtime on weekdays beyond contracted hours.
    - **2.0** — overtime on a rest day (Sunday) or public holiday.
  - These values must be confirmed against the current ELRA provisions before setting production defaults. They are stored as inputs by HR, not calculated by the system.
  - Alternatively, HR can input a fixed overtime monetary amount (bypassing the hours-based formula entirely).
- **Bonuses, Commissions, Incentives**: Subject to full taxation and statutory deductions unless explicitly configured otherwise.
- **One-time Payments**: Added to earnings in a specific open payroll period and subject to standard taxation.

---

## 5. Deduction Rules

- **Mandatory Deductions**: NSSF, PAYE, and Court Orders. These cannot be bypassed unless a legal exemption flag is active.
- **Optional Deductions**: Configured per employee (SACCO, Insurance, Welfare, Union, Wedding Contributions, Funeral Funds). Must support fixed amounts or percentages of Basic Salary.
- **Loans & Salary Advances**: 
  - Track total loan amount, installment amount, and outstanding balance.
  - Each payroll run automatically reduces the outstanding balance.
  - **Final Installment Rule**: If the loan's outstanding balance is less than the scheduled installment amount at the time of processing, the engine must deduct only the remaining outstanding balance (not the full installment amount). The loan is then automatically closed. This prevents over-deduction on the final repayment.
- **Deduction Priority Order (CANONICAL 5-TIER ORDER)**:
  1. **Statutory Deductions** (NSSF, PAYE)
  2. **Court Orders** (Legally binding attachments)
  3. **Company Loans & Salary Advances** (To protect employer funds)
  4. **Third-party Loans** (e.g., SACCOs, Banks)
  5. **Voluntary Deductions** (Union, Welfare, Insurance)

---

## 6. Payroll Edge Cases

- **Working Days Configuration**: `Total Working Days` must be a globally shared configuration (e.g., fixed 26 days, or dynamic calendar days minus public holidays) ensuring proration and unpaid-leave formulas use the exact same denominator. This value is stored in `company_profile.working_days_per_month`.
- **Mid-month Hires & Terminations**: Basic salary must be prorated. Formula: `(Working Days in Period / Total Working Days in Month) * Basic Salary`.
  - **Termination Date Rule**: The termination date itself is counted as the employee's **last worked day** and is included in the "Working Days in Period" count for proration purposes.
- **Leave Without Pay (Unpaid Leave)**: 
  - Deducted from the Basic Salary. 
  - Formula: `(Basic Salary / Total Working Days) * Unpaid Leave Days`. 
- **Paid Leaves (Annual, Sick, Maternity, Paternity)**:
  - Do NOT reduce the Basic Salary. The employee receives normal taxable income for these days.
- **Late Arrivals & Unauthorized Absences**:
  - If enforced by company policy, unauthorized absences or significant late arrivals are mathematically treated as Unpaid Leave and deducted from the Basic Salary. Unauthorized absences are recorded as `leave_records` of type `unpaid` before the payroll engine processes them — there is no separate deduction code path.
- **Negative Net/Basic Guardrails**: 
  - Basic Salary calculation (after unpaid leave) floors at zero. It cannot become negative.
  - Net salary is prevented from going negative by the strict deduction priority, preview blocking logic, and runtime transaction rollbacks.
- **Employee Termination with Active Loans**: When an employee is terminated, the system must display a warning if the employee has active loans with an outstanding balance greater than zero. HR must acknowledge this warning before the termination is confirmed. The outstanding loan does not automatically become a lump-sum deduction; it must be handled via a supplementary run or a formal write-off process.
- **Payroll Adjustments**: Must be handled as explicit "Custom Earnings" or "Custom Deductions" on the current payroll. No silent manual overrides of the final calculated net pay.
- **Retroactive Salary Changes & Arrears (CRITICAL ALGORITHM)**: 
  - Arrears must not be lump-summed into the current month to prevent progressive tax bracket creep. 
  - **Algorithm**:
    1. Identify the historical periods affected by the arrears.
    2. For each affected month, retrieve the snapshot of original Gross Taxable Salary, Tax Bands, and Rates.
    3. Add the apportioned arrears amount to the historical month's Gross Taxable Salary.
    4. Recompute the theoretical PAYE and Statutory deductions for that historical month. *(Note: For schemes with a contribution ceiling, cap the recomputed statutory at the historical period's configured ceiling).*
    5. Subtract the originally paid PAYE/Statutory from the recomputed PAYE/Statutory to get the difference (Delta).
    6. Sum the Gross Arrears and sum the Tax/Statutory Deltas for all affected months.
    7. Process the Total Gross Arrears as an earning and the Total Tax/Statutory Delta as a specific deduction in the current month's payroll.
  - Workings for each step must be recorded in the `payroll_arrears_workings` table for full audit traceability.
- **Payroll Reversal / Void Runs**: 
  - The system must support cancelling or voiding a Locked (but not Filed) payroll run if a mistake is found post-approval.
  - Reversals must track an audit trail of the voided records (recorded in `payroll_run_state_logs`) and reset any deducted loan balances (restoring `loan_installments.outstanding_balance_after`).
  - A reversed run transitions to `Reversed` status and is retained in the database with all its `payroll_run_results` and `payslip_line_items` records intact.
  - **TRA Amended Return Workflow**: Voiding an internal payroll does NOT un-file government returns. 
    1. The system tracks a "Filing Status" (Unfiled vs Filed) for each run. 
    2. If a payroll is marked "Filed", it is locked from silent internal voiding.
    3. Instead, the system must trigger an **Amended Return Workflow**: Generate an "Amended" run linked to the original via `original_run_id`, perform corrections, calculate the deltas, and generate specific Amended TRA/NSSF export files containing only the corrected values. Once remitted, the Amended run is marked as "Filed".

---

## 7. Rounding Rules

- **Currency**: Tanzanian Shilling (TZS).
- **Precision**: 
  - All intermediate calculations (tax, pension, prorations) must retain at least 4 decimal places in code to prevent compounding errors.
  - **Final Net Salary and Final Deductions**: Must be rounded to the nearest whole number (0 decimals), as TZS does not actively use cents in physical or electronic banking transfers.
  - Rounding should use standard arithmetic half-up rounding.
- **Residual Absorption**: 
  - To ensure `Gross - Deductions = Net` balances exactly without a 1-2 TZS gap, calculate the Net Salary precisely, apply rounding, and designate a specific line item (typically PAYE) to absorb the fractional rounding residual.
  - **Documentation Rule**: The decision to absorb rounding residuals into PAYE must be formally documented in the monthly payroll reconciliation reports. The report must include a distinct 'Rounding Adjustment' column or footnote so finance teams can trace the exact variation.

---

## 8. Compliance & Audit Rules

- **Payroll Locking**: 
  - Once a payroll period is "Approved" and "Locked", absolutely no changes can be made to that run. 
  - Modifications require generating a formal "Correction" run or executing the Amended Return workflow.
- **Data Immutability**: 
  - The payroll engine must store the exact statutory rates, bands, and formulas used during that specific month's run. Historical payslips must rely on snapshot data (stored in `payroll_run_results` and `payslip_line_items` tables), not dynamic queries to current settings.
  - Deactivating an allowance configuration must not break historical payslips.
- **Audit Trail**: Every change to an employee's Basic Salary, Tax Bands, Statutory Rates, or Loan Balances must be logged in the `audit_logs` table (recording user, timestamp, old value, and new value).
- **Legal Compliance Expectations**: The architecture must support exact reproduction of historical payroll calculations if audited by the TRA (Tanzania Revenue Authority).

---

## 9. Payroll Governance & Lifecycle Rules

### 9.1 Payroll State Machine

Every payroll run must exist in exactly one state at any time, with explicit transition rules:

- **Draft**: Run is created, employees loaded. *Allowed:* Add/remove employees, edit manual inputs. *Forbidden:* Exporting, locking.
- **Validated**: Pre-flight checks passed. *Allowed:* Proceed to calculation. *Forbidden:* Proceeding if Errors exist.
- **Preview**: Calculations are complete and ready for review. *Allowed:* Regenerate calculation, view reports.
- **Approved**: Maker has submitted the run. *Allowed:* Checker approval or rejection. *Forbidden:* Calculation changes. *(Note: On rejection, the run reverts to Draft, the rejection reason is recorded in `payroll_run_state_logs`, the Maker is notified, and the run must be fully recalculated before resubmission.)*
- **Locked**: Checker has approved the run. *Allowed:* Generation of bank/statutory export files. *Forbidden:* Any edits, additions, or deductions to payroll data. *Note: "Approved" in common speech means the Checker has confirmed the run; the system state is `Locked` once committed.*
- **Filed**: Run is finalized and exported to TRA/NSSF. *Forbidden:* Internal voiding (must go through the Amended Return workflow).
- **Amended**: A correction run linked to a Filed original via `original_run_id`. *Allowed:* corrections, delta calculation, generation of Amended TRA/NSSF export files. *Forbidden:* adding employees not in the original run. Transitions to Filed once remitted.
- **Reversed**: A Locked (but not Filed) run that has been voided by an administrator. All results are retained for audit. Loan balances are restored. This state is terminal — a reversed run cannot be re-activated.

**State Transition Table:**

| Current State | Trigger Action         | Role Required  | Next State           |
| :------------ | :--------------------- | :------------- | :------------------- |
| Draft         | Run Validation         | Maker          | Validated (if pass)  |
| Validated     | Execute Calculation    | Maker          | Preview              |
| Preview       | Submit for Approval    | Maker          | Approved             |
| Approved      | Approve Run            | Checker        | Locked               |
| Approved      | Reject Run             | Checker        | Draft                |
| Locked        | Reverse Run            | Admin          | Reversed             |
| Locked        | Mark as Filed          | Checker/Admin  | Filed                |
| Filed         | Initiate Correction    | Admin          | Amended              |
| Amended       | Mark as Filed          | Checker/Admin  | Filed                |

---

### 9.2 Payroll Period Rules

A payroll period (e.g., July 2026) is the explicit container for a run. The following system-level constraints must be enforced:

- **Single Open Period**: Only one period may be in an `open` state at a time.
- **Sequential Flow**: You cannot open August before July's standard payroll run has reached the `Locked` state. The prerequisite state is `Locked` (not merely `Approved`).
- **No Future Approvals**: You cannot approve a future period.
- **Closed Periods**: A period transitions to `closed` automatically when its primary run is `Locked`. It cannot be reopened without an explicit admin action with a recorded justification. When reopened, the period returns to `open` status. The reopening action is recorded in `audit_logs`.
- *Rationale*: Enforcing this prevents simultaneous duplicate runs and double TRA filings.

---

### 9.3 Maker / Checker Rule (Segregation of Duties)

- The user who creates and calculates a payroll run (Maker) must not be the same user who approves it (Checker).
- This constraint must be enforced at the **database transaction level** via a CHECK constraint: `CHECK (submitted_by_user_id != approved_by_user_id)`.
- The `payroll_run_state_logs` table must definitively store both the Maker identity (on submission) and the Checker identity (on approval/rejection). This is a non-negotiable requirement for TRA and external financial audits.

---

### 9.4 Concurrency Rules

- The system must guarantee that concurrent modification attempts against a single payroll run are strictly serialized or rejected.
- **Required Outcome**: If two authorized users attempt a state transition (e.g., Approve) or a data mutation simultaneously, exactly one operation must succeed, and the second operation must cleanly abort with an error indicating the state has changed.
- The developer is responsible for implementing the necessary underlying mechanism (e.g., optimistic locking via a `version` integer column, atomic state queries, or database row locks) to enforce this outcome.

---

### 9.5 Pre-flight Validation Engine

Before the calculation engine starts processing, a pre-flight validation sweep must run. Catching issues pre-flight prevents expensive mid-run failures.

- **Severity Levels**:
  - **ERROR**: Blocks the run. Must be resolved before entering the *Validated* state.
  - **WARNING**: Flags anomalies (e.g., unusually high overtime) but allows the run to proceed if acknowledged.
  - **INFORMATION**: Highlights missing non-critical data.
- **Mandatory ERROR Checks** (Engine must refuse to proceed):
  - Employees with no basic salary configured.
  - Inactive employees included in the run incorrectly.
  - Employees with no tax profile assigned.
  - Duplicate employee records in the same run.
  - Duplicate open runs in the same period.
  - A hire date in the future.
  - Missing department or cost center assignments.
  - Negative allowance values.
  - Any employee whose net pool after statutory deductions is zero or negative before post-tax deductions even begin.
- Validation results are persisted to the `payroll_preflight_results` table so the Maker can review them after navigation and so there is an audit record of what checks were run.

---

## 10. Operational Appendix (Runbook & Maintenance)

- **Compliance Calendar**: To ensure statutory deadlines are met, the system runbook and HR reporting schedules must align with these mandatory filing dates:
  - **PAYE + SDL**: Remitted by the 7th of the following month (via TRA e-filing portal).
  - **NSSF**: Remitted within 30 days of period close (via NSSF employer portal).
  - **WCF**: Remitted by the end of the following month (via wcf.go.tz).
  - **Annual WCF Reconciliation**: Due by 31 March (covers the period from 1 March to 28 February).
  - **Annual PAYE Reconciliation**: Due after 30 June (typically within the prescribed period following the close of the financial year).
- **Public Holiday Maintenance**: The system must include an interface for HR/Admins to configure annual public holidays. This ensures that the dynamic "Total Working Days" variable remains perfectly accurate across proration and unpaid leave calculations without requiring hardcoded developer updates each year.
- **Audit Retention Policy**: To satisfy TRA audit requirements, the payroll database must implement a strict retention policy. No historical payroll run, associated payslips, or snapshot calculation rates should ever be hard-deleted. System archiving strategies must maintain read-only access to this data for our internal policy of **7 to 10 years** to confidently defend past payroll logic (Note: the legal minimum retention floor under the Income Tax Act is **5 years**).
