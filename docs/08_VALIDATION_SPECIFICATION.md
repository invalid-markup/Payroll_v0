# 08_VALIDATION_SPECIFICATION.md
# Validation Specification Document
## Tanzanian Payroll & HR Management System

---

## 1. Overview
### Purpose
This document is the authoritative validation specification that defines every validation rule the system will enforce during implementation. 
### Scope
It covers Laravel Form Requests, API Request Validation, Service Layer Validation, Business Rule Validation, Database Integrity Validation, Security Validation, and Payroll Workflow Validation.
### Objectives
To provide a single source of truth for all data integrity rules.
### Relationship with other specifications
Derived strictly from `01_BUSINESS_RULES.md`, `02_FUNCTIONAL_REQUIREMENTS.md`, `03_DATABASE_SPECIFICATION.md`, `05_SYSTEM_ARCHITECTURE.md`, `06_SECURITY_SPECIFICATION.md`, and `07_API_SPECIFICATION.md`.
### Validation Philosophy
Validation occurs in layers (Client, Request, Service, Database) ensuring data integrity, security, and accurate payroll processing.

---

## 2. Validation Standards
- **Validation layers:** Defense in depth.
- **Client validation:** Quick feedback via frontend.
- **API validation:** Immediate rejection of malformed payloads.
- **Laravel Form Requests:** Primary entry point for HTTP validation.
- **Business validation:** Enforced in the Service layer (e.g., Maker/Checker logic).
- **Database constraints:** Final safeguard (foreign keys, unique constraints).
- **Security validation:** RBAC policies checked before controller logic executes.

---

## 3. Common Validation Rules
- **Required fields:** Cannot be null or empty string.
- **Nullable fields:** Explicitly allowed to be null.
- **String lengths:** Max 255 characters unless specified otherwise.
- **Numeric precision:** Integers for IDs; Decimals for currency.
- **Currency precision:** `DECIMAL(15,4)` — 4 decimal places for all internal storage (per `03_DATABASE_SPECIFICATION.md` §2.7 and §4.1); rounded to 0 decimal places for final net salary output.
- **Dates:** Valid ISO 8601 format (YYYY-MM-DD).
- **UUIDs:** Not used for primary keys (using bigIncrements).
- **Booleans:** Must be true, false, 1, or 0.
- **Enums:** Must strictly match defined sets.
- **Email:** RFC valid email format.
- **Phone numbers:** Tanzanian format validation where applicable.
- **TIN:** 9 digits (XXX-XXX-XXX).
- **NSSF Number:** Standard format compliance.
- **Bank account:** Numeric strings.
- **Branch codes:** Alphanumeric.
- **Payroll IDs:** Exists in database.
- **Employee numbers:** Unique string constraints.
- **Unique constraints:** Enforced at database and Form Request levels.
- **Foreign key validation:** `exists:table,id` validation on all relations.
- **Existence validation:** Ensuring parent records exist.
- **Soft delete handling:** Checked using `whereNull('deleted_at')`.

---

## 4. Module Validation Rules

### Authentication
- `email`: required, email, exists in users.
- `password`: required, min:8.

### Company
- `name`: required, string, max:255.
- `tin`: required, string, regex:/^\d{3}-\d{3}-\d{3}$/.
- `working_days_per_month`: required, integer, min:1, max:31.

### Employees
- `employee_number`: required, unique, string.
- `first_name`, `last_name`: required, string, max:255.
- `status`: required, in:active,terminated.
- `department_id`: required, exists:departments,id.
- `branch_id`: required, exists:branches,id.

### Salary Structures
- `code`: required, string, unique.
- `name`: required, string, max:255.

### Earnings / Deductions
- `amount`: required_without:percentage, numeric, min:0.
- `percentage`: required_without:amount, numeric, min:0, max:100.
- `effective_from`: required, date.

### Payroll Processing
- `payroll_period_id`: required, exists:payroll_periods,id.
- Maker != Checker validation on state transitions.

### Loans
- `employee_id`: required, exists:employees,id.
- `total_amount`: required, numeric, min:1.
- `installment_amount`: required, numeric, max:total_amount.
- `start_period_id`: required, exists:payroll_periods,id.
- *Note: `loan_type` does not appear in the `loans` table schema in `03_DATABASE_SPECIFICATION.md` §5.9. No ENUM is defined for it. Do not add validation for an undefined column. If a loan categorisation field is required, it must first be added to the database specification.*

### Leave
- `employee_id`: required, exists:employees,id.
- `leave_type`: required, in:annual,sick,maternity,paternity,unpaid,unauthorized_absence.
- `start_date`: required, date.
- `end_date`: required, date, after_or_equal:start_date.
- `days`: required, integer, min:1, max:31.
- Unpaid leave days cannot exceed period working days.

### Attendance (Overtime & Absence)
- `employee_id`: required, exists:employees,id.
- `date`: required, date.
- Overtime: `hours` (numeric) or `fixed_amount` (numeric) must be provided.
- Absence: `days` (integer, min:1).

### Payroll Periods
- `name`: required, string, max:255.
- `start_date`: required, date.
- `end_date`: required, date, after_or_equal:start_date. *(Matches `03_DATABASE_SPECIFICATION.md` §7.2 CHECK constraint: `end_date >= start_date`, which permits single-day periods.)*
- Cannot open if another period is open.

### Statutory Configurations
- `code`: required, string.
- `rate_percentage`: required, numeric, min:0, max:100.
- `effective_from`: required, date.

### System Settings
- Config payloads must match provider expected fields.
- Admin override justifications must be min:10 characters.

### Additional High-Risk Fields
- `overtime_rate_multiplier`: numeric, min:0. Typical statutory values are 1.5× or 2.0× per ELRA (`01_BUSINESS_RULES.md` §4). No upper hard cap is defined in the spec, but values outside this range should log a warning.
- `payroll_run.type`: required, in:standard,supplementary,amended_return. Matches the `payroll_run_type` ENUM in `03_DATABASE_SPECIFICATION.md`.
- `statutory_configuration.code`: required, in:nssf_employer,nssf_employee,wcf,sdl. Matches the statutory code ENUM.
- `resident_status`: required, in:resident,non_resident. Applied on employee records for PAYE bracket selection.
- `employment_type`: required, in:permanent,contract,casual. Applied on employee records.

---

## 5. Payroll Engine Validation
### State Transitions
- **`draft` → `validated`:** Requires passing all pre-flight data checks (FR-PROC-005).
- **`validated` → `preview`:** Requires successful calculation; snapshots written to `payroll_run_results` and `payslip_line_items` at this point (FR-PROC-006).
- **`preview` → `approved`:** Maker submits; blocked if ERROR-level flags remain (FR-PROC-007/008). Records `submitted_by_user_id`.
- **`approved` → `locked`:** Checker approves; `approved_by_user_id` must differ from `submitted_by_user_id` (Maker/Checker enforcement, FR-PROC-009). Marks snapshots as permanent Hard Records.
- **`locked` → `filed`:** Requires successful export (FR-PROC-010).
- **`locked` → `reversed`:** Requires System Administrator justification; restores loan balances (FR-PROC-012).
### General Engine Rules
- Prevent multiple open periods.
- Prevent modifying `locked` or `filed` runs.
- Snapshots (`payroll_run_results`, `payslip_line_items`) are generated when the run transitions to `preview`. The `approved → locked` transition marks them as permanent Hard Records — they are never regenerated.
- Concurrent processing: Prevent race conditions using pessimistic DB locks (`lockForUpdate()`, per `05_SYSTEM_ARCHITECTURE.md` §4.3).

---

## 6. Effective Dating Validation
- `effective_from` must be a valid date.
- Multiple records with the same `effective_from` date for the same entity and code are rejected (duplicate effective date constraint). *Note: There is no `effective_to` column in the schema — the system resolves the active record by selecting the row with the greatest `effective_from` ≤ target date (`03_DATABASE_SPECIFICATION.md` §9.1). Overlapping ranges are structurally impossible by design, not by a range-check validation.*
- Historical data cannot be modified once it has been applied to a `locked` payroll run.
- Engine resolution rule: active rate = greatest `effective_from` ≤ period end date.

---

## 7. Financial Validation
- **Currency precision:** `DECIMAL(15,4)` used for all monetary storage. Final net salary is rounded to 0 decimal places; the rounding residual is logged in the `rounding_adjustment` column.
- **Negative values:** Rejected for gross pay, net pay, earnings amounts, and standard deduction amounts.
- **Percentage limits:** 0 to 100 inclusive.
- **Loan balances:** Installment deduction cannot exceed the employee's outstanding loan balance.
- **Net pay validation:** Final net salary after all deductions cannot be negative.

---

## 8. Security Validation
- **Authentication:** Valid Sanctum token (opaque token stored in `personal_access_tokens` table, per `05_SYSTEM_ARCHITECTURE.md` §7.1). Not a JWT.
- **Authorization:** Enforced via Spatie Permissions (e.g., `hr_manager` role).
- **Maker/Checker:** Identity of submitter stored and checked against approver.
- **Immutable records:** Attempting to UPDATE/DELETE locked payslips or audit logs is blocked.

---

## 9. Database Integrity Validation
- **Foreign keys:** Strictly enforced on DB schema.
- **Unique constraints:** Email, TIN, Employee Number, Branch Code.
- **Append-only:** Audit logs, salary history, snapshot tables.

---

## 10. Error Handling Standards
- **Format:** Standard JSON with `message` and `errors` array.
- **Status Codes:** 422 (Validation), 403 (Forbidden), 409 (Conflict/Concurrency).

---

## 11. Laravel Implementation Mapping
- **Form Requests:** `App\Http\Requests\Payroll\*`
- **Policies:** `App\Policies\*` mapping to RBAC.
- **Database:** Migrations defining foreign keys and unique indexes.
- **Transactions:** `DB::transaction()` around state transitions and period closures.

---
