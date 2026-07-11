# 03_DATABASE_SPECIFICATION.md
# Database Specification
## Tanzanian Payroll & HR Management System

---

| Attribute   | Value                                                                          |
|-------------|--------------------------------------------------------------------------------|
| Version     | 1.0                                                                            |
| Status      | Draft                                                                          |
| Relates To  | `00_SPEC.md`, `01_BUSINESS_RULES.md`, `02_FUNCTIONAL_REQUIREMENTS.md`         |
| Feeds Into  | `04_DATABASE_ERD.md`, `05_SYSTEM_ARCHITECTURE.md`                             |
| Owner       | Solutions Architect / Database Architect                                       |
| Audience    | Software Engineers, QA Engineers, AI Coding Agents, Security Auditors          |

---

## Table of Contents

1. Overview
2. Design Principles
3. Naming Standards
4. Data Standards
5. Entity Catalog *(Section 5 — forthcoming)*
6. Relationships *(Section 6 — forthcoming)*
7. Constraints *(Section 7 — forthcoming)*
8. Snapshot Strategy *(Section 8 — forthcoming)*
9. Effective Dating Strategy *(Section 9 — forthcoming)*
10. Audit Strategy *(Section 10 — forthcoming)*
11. Indexing Strategy *(Section 11 — forthcoming)*
12. Performance Strategy *(Section 12 — forthcoming)*
13. Archiving Strategy *(Section 13 — forthcoming)*
14. Backup Considerations *(Section 14 — forthcoming)*

---

## 1. Overview

### 1.1 Purpose

This document is the authoritative reference for the design, structure, and governance of the relational database underpinning the Tanzanian Payroll & HR Management System. It defines the foundational principles that every table, column, relationship, constraint, and index in the system must conform to.

The database is not merely a persistence layer. In an enterprise payroll context, the database is a **compliance artefact**. Every approved payroll run, every payslip, every tax calculation, and every audit event must be reconstructable — accurately and completely — by querying this database alone, without any dependence on application logic, cached state, or external systems.

This document exists to make that guarantee enforceable and to prevent design decisions that would compromise it.

### 1.2 Scope

This specification governs the entire persistent data layer for the MVP as defined in `00_SPEC.md`. It covers:

- All operational data entered by HR and Finance users.
- All payroll calculation inputs and outputs.
- All statutory rate configurations and their effective date histories.
- All audit and compliance records.
- All notification tracking.
- All user identity and access control data.

It does **not** govern:
- Application-layer caching (e.g., in-memory or cache-store data that is ephemeral by design).
- File storage (generated PDF payslips, CSV bank exports, Excel reports).

### 1.3 Supported Business Domains

The database supports the following business domains, each corresponding to one or more modules defined in `02_FUNCTIONAL_REQUIREMENTS.md`:

| Domain                          | FRD Module Reference                         |
|---------------------------------|----------------------------------------------|
| Identity & Access Control       | Module 4.01 — Authentication & Authorization |
| Organizational Structure        | Module 4.02 — Company Management             |
| Employee Lifecycle              | Module 4.03 — Employee Management            |
| Salary Structure & History      | Module 4.04 — Salary Structure Management    |
| Earnings Configuration          | Module 4.05 — Earnings Management            |
| Deductions Configuration        | Module 4.06 — Deductions Management          |
| Statutory Rate Configuration    | Module 4.07 — Statutory Compliance Config    |
| Leave Records                   | Module 4.08 — Leave Management               |
| Attendance & Overtime           | Module 4.09 — Attendance & Overtime          |
| Loan & Advance Management       | Module 4.10 — Loan Management                |
| Payroll Period Governance       | Module 4.11 — Payroll Period Management      |
| Payroll Run Lifecycle & Results | Module 4.12 — Payroll Processing Engine      |
| Payslip Records                 | Module 4.13 — Payslip Management             |
| Bank Export Tracking            | Module 4.14 — Bank Export                    |
| Notification Dispatch           | Module 4.16 — Notifications                  |
| Audit Trail                     | Module 4.17 — Audit Logs                     |
| System Configuration            | Module 4.18 — System Settings                |

*Note: Role storage for the Identity & Access Control domain is delegated to `spatie/laravel-permission`; see §5.1 note.*

### 1.4 Source Documents

All design decisions in this specification derive directly from the three governing documents:

- **`00_SPEC.md`** — System scope, technical requirements, security requirements, and MVP module list.
- **`01_BUSINESS_RULES.md`** — Payroll calculation logic, statutory compliance rules, rounding rules, payroll state machine, Maker/Checker constraints, concurrency rules, audit retention policy, and the retroactive arrears algorithm.
- **`02_FUNCTIONAL_REQUIREMENTS.md`** — User roles, functional requirements per module, business event registry, and acceptance criteria.

### 1.5 Technical Environment

The application layer is built on **Laravel 13 / PHP 8.5** as defined in `00_SPEC.md`. This is relevant to this document in two ways:
- **Precision Mathematics:** The PHP `BCMath` extension must be used for all monetary arithmetic in the application layer to avoid floating-point rounding errors. The database guarantees precision via `DECIMAL` types; the application must not bypass this with float arithmetic.
- **ORM Behaviour:** Laravel's Eloquent ORM soft-delete behaviour (`deleted_at`) is assumed throughout this specification. Any deviation from standard Eloquent patterns (e.g., custom scopes) must be documented per-table.
- **UUID Support:** PostgreSQL's native `uuid` type is used for all primary keys and foreign keys. Laravel migrations use `->uuid()` / `->foreignUuid()` helpers, which map to PostgreSQL's native `UUID` column type.

### 1.6 Design Goals

The database design is governed by five overarching goals, listed in order of precedence:

1. **Correctness**: Every number in every payslip must be exactly reproducible from the stored data alone. There must be no ambiguity in what rates, rules, or inputs were used for any historical calculation.

2. **Compliance**: The database must support TRA audit requirements — specifically the ability to reproduce any historical payroll calculation if audited. Records must be retained for a minimum of 5 years (Income Tax Act) and up to 10 years per internal policy. See `01_BUSINESS_RULES.md` Section 10.

3. **Immutability of Financial Records**: Approved and locked payroll data, payslips, and audit logs must be structurally protected from modification. Protection must be enforced at the database design level, not only in application logic.

4. **Traceability**: Every financial record, configuration change, and administrative action must be traceable to a specific user, at a specific point in time, with the before and after state captured. See `01_BUSINESS_RULES.md` Section 8.

5. **Extensibility**: The schema must accommodate future regulatory changes (new tax bands, new statutory schemes) and future features (Phase 2, Phase 3) without requiring destructive migrations to historical data.

---

## 2. Design Principles

This section defines the non-negotiable design principles that govern every entity in the schema. Any table design that violates these principles must be escalated for review before implementation.

---

### 2.1 Principle: Immutable Payroll Snapshots

**Statement:** Once a payroll run reaches the `locked` state, the data generated by that run — including all earnings, deductions, statutory amounts, and net pay values for every employee — must be permanently immutable.

**Rationale:** The payroll engine processes data that may change over time: employees get salary revisions, tax bands change annually, allowances are restructured. If payslips and run results pointed to live configuration records, a salary revision today would silently alter last month's historical payslip — a severe compliance failure.

**Implementation Rule:** The payroll calculation engine must write a point-in-time snapshot of all calculation inputs and outputs for each employee in the run into dedicated result/payslip line-item tables. These snapshot records must:

- Be written at the time of calculation (Preview state).
- Be permanently preserved and never overwritten, even if the source configuration (e.g., a tax band) is later modified or deactivated.
- Store all monetary values at `DECIMAL(15,4)` precision.
- Store any structured configuration data (e.g., the specific PAYE bracket structure applied) as a JSON snapshot column.
- Be flagged as belonging to a specific payroll run, so that regenerating a calculation does not silently overwrite the prior snapshot — it must create a new version.

**Reference:** `01_BUSINESS_RULES.md` Section 8 (Data Immutability); `02_FUNCTIONAL_REQUIREMENTS.md` FR-PROC-015.

---

### 2.2 Principle: Effective-Dated Statutory Records

**Statement:** No statutory rate, tax bracket, contribution percentage, or threshold shall ever be updated in place. Every change to a statutory configuration value must be recorded as a new row with a new `effective_from` date, preserving the full history of all prior values indefinitely.

**Rationale:** Tanzania's Finance Act is amended periodically. PAYE brackets changed from year to year. NSSF contribution bases and rates are subject to regulatory revision. A system that overwrites statutory rates cannot correctly answer the question "What rate was applied in March 2024?" — which is a mandatory audit capability. See `01_BUSINESS_RULES.md` Section 2 (IMPORTANT note on configurability).

**Implementation Rule:** All statutory configuration tables must follow the effective-dating pattern:

- Every rate or bracket record must carry an `effective_from` column (`DATE`).
- No `effective_to` column is required; the active record for a given date is determined by the record with the greatest `effective_from` date that is still ≤ the target date (e.g., the payroll period end date).
- Existing records must never be updated once they have been used in a locked payroll run.
- Statutory records that have been used in a locked run must be treated as hard records and must not be deletable via any application interface.

**Reference:** `01_BUSINESS_RULES.md` Section 2; `02_FUNCTIONAL_REQUIREMENTS.md` FR-STAT-001 through FR-STAT-006.

---

### 2.3 Principle: Append-Only Salary History

**Statement:** An employee's Basic Salary must never be updated in place. Every salary change must be recorded as a new row with an `effective_from` date. The previous record is preserved indefinitely.

**Rationale:** Salary history is required for retroactive arrears calculations, for audit purposes, and for TRA verification. An overwritten salary record destroys the audit chain. Additionally, the retroactive arrears algorithm defined in `01_BUSINESS_RULES.md` Section 6 requires accessing the exact historical salary for each affected period.

**Implementation Rule:** The table that stores employee salary amounts must operate as an append-only ledger:

- A new salary amount is always a new row — never an `UPDATE` to an existing row.
- Each row carries an `effective_from` date.
- The salary applicable to a payroll period is the record with the greatest `effective_from` date that is still ≤ the payroll period end date.
- Salary records used in a locked payroll run are hard records and must never be deletable.

**Reference:** `01_BUSINESS_RULES.md` Section 6 (Retroactive Salary Changes & Arrears); `02_FUNCTIONAL_REQUIREMENTS.md` FR-SAL-003, FR-SAL-004, Acceptance Criteria for Module 4.03.

---

### 2.4 Principle: Soft Deletes on Operational Records

**Statement:** Operational entities — employees, departments, branches, earning types, deduction types, pay groups — must never be physically deleted from the database. Instead, they must be logically deactivated via a `deleted_at` timestamp column.

**Rationale:** Physical deletion of an employee record would orphan their historical payslips, loan records, leave history, and audit log entries, breaking referential integrity and destroying audit traceability. Deactivating a department that has processed payroll must preserve that historical association. This also enables the system to support restoring accidentally deactivated records.

**Implementation Rule:**

- All operational entity tables must include a `deleted_at TIMESTAMP NULL` column.
- A record where `deleted_at IS NOT NULL` is considered logically deleted.
- All application queries against these tables must filter on `deleted_at IS NULL` by default.
- Records with a non-null `deleted_at` remain fully queryable by authorized roles (e.g., Auditor) for historical reference.
- The act of setting `deleted_at` must itself generate an audit log entry.
- Physical `DELETE` statements are forbidden against any table covered by this principle.

**Entities in scope for soft delete:**

Employees, Branches, Departments, Cost Centers, Earning Types, Deduction Types, Salary Structures, Pay Groups, Loans (status-driven deactivation, supplemented by soft delete), Leave Types, Notification Templates, User Accounts.

**Reference:** `00_SPEC.md` (Security Requirements — prevent data loss); `02_FUNCTIONAL_REQUIREMENTS.md` Module 4.03 FR-EMP-009.

---

### 2.5 Principle: Hard Records on Financial and Audit Data

**Statement:** Financial records — payslip line items, payroll run results, payroll run state records — and audit log entries must never be deletable through any application interface, by any user role, including System Administrator.

**Rationale:** These records form the legal and compliance backbone of the system. TRA audits demand that historical payroll calculations be precisely reconstructable. A deleted payslip or a deleted audit entry constitutes a compliance violation. This protection must be structural — not merely an application-layer policy that can be bypassed.

**Implementation Rule:**

- Hard record tables must not include a `deleted_at` column.
- Application-layer policies (Policies / Guards) must deny all delete operations against these tables.
- No `DELETE` migrations or seeder teardown scripts must ever target these tables in a production environment.
- Reversals and corrections are handled by creating **new correcting records** (e.g., a reversal run, an amended run) — never by modifying or removing the original records.
- Database-level constraints (e.g., no cascade deletes pointing into these tables from other tables) must reinforce this protection.

**Entities in scope for hard records:**

Payroll Runs, Payroll Run Employee Results (payslip snapshots), Payslip Line Items (earnings/deductions per payslip), Audit Log Entries, Bank Export Records, Statutory Rate Records (once used in a locked run), Salary History Records (once used in a locked run).

**Reference:** `01_BUSINESS_RULES.md` Section 8 (Compliance & Audit Rules); `01_BUSINESS_RULES.md` Section 10 (Audit Retention Policy — minimum 5-year legal floor, internal 7–10 year policy); `02_FUNCTIONAL_REQUIREMENTS.md` FR-AUD-006.

---

### 2.6 Principle: Maker and Checker Identities Permanently Bound to Payroll Runs

**Statement:** Every payroll run record must permanently store the user ID of the Maker who submitted the run and the user ID of the Checker who approved or rejected it. These fields must be non-nullable at the point of the respective action and must never be overwritable.

**Rationale:** The Maker/Checker segregation of duties is a non-negotiable TRA and external audit requirement. The audit trail for any payroll run must definitively answer: "Who prepared this run?" and "Who authorized it?" Storing only in the application log is insufficient — it must be embedded in the payroll run record itself.

**Implementation Rule:**

- The payroll runs table must include `submitted_by_user_id` (Foreign Key → users) and `approved_by_user_id` (Foreign Key → users) as explicit columns.
- `submitted_by_user_id` is set when the run transitions to `approved` state. Once set, it must not be modifiable.
- `approved_by_user_id` is set when the run transitions to `locked` state. Once set, it must not be modifiable.
- The system must enforce at the database transaction level that `submitted_by_user_id ≠ approved_by_user_id`. This constraint must not be enforced solely in application code.
- Rejection events must store the rejecting user's ID and the rejection reason in a dedicated payroll run state log table (append-only).

**Reference:** `01_BUSINESS_RULES.md` Section 9.3 (Maker/Checker Rule); `02_FUNCTIONAL_REQUIREMENTS.md` FR-PROC-009, FR-AUTH-001 Acceptance Criteria.

---

### 2.7 Principle: All Money Stored as DECIMAL(15,4) Internally

**Statement:** Every monetary value stored in the database — salary, earnings, deductions, tax amounts, contributions, loan balances, net pay — must use the `DECIMAL(15,4)` data type. Floating-point types (`FLOAT`, `DOUBLE`, `REAL`) are absolutely prohibited for any monetary column.

**Rationale:** Floating-point arithmetic introduces binary rounding errors that compound across payroll calculations involving thousands of transactions. A 0.0001 TZS error per calculation multiplied across 500 employees over 12 months produces inaccurate payroll reconciliation reports and, more critically, inaccurate statutory return filings. `DECIMAL` provides exact numeric storage.

The 4 decimal places allow the system to store intermediate calculation precision as required by `01_BUSINESS_RULES.md` Section 7, while the overall `(15,4)` range supports payroll amounts up to 999,999,999,999 TZS before the decimal point — sufficient for all foreseeable payroll volumes.

**Implementation Rule:**

- All money columns must be declared as `DECIMAL(15,4)`.
- Intermediate calculation values (proration results, pro-rated tax, etc.) are stored at full 4-decimal precision.
- The final Net Salary figure stored on the payslip snapshot is stored at `DECIMAL(15,4)` internally but must be rounded to 0 decimal places when presented in UI, reports, and bank export files.
- The rounding residual (difference between mathematically precise net and rounded net) must be captured as a distinct `rounding_adjustment` column on the payslip result record, per `01_BUSINESS_RULES.md` Section 7.
- No `FLOAT`, `DOUBLE`, `REAL`, or `INTEGER` types may be used for monetary values, even for values that appear to always be whole numbers.

**Reference:** `01_BUSINESS_RULES.md` Section 7 (Rounding Rules); `02_FUNCTIONAL_REQUIREMENTS.md` Section 4 Data Standards; `00_SPEC.md` (Performance Requirements — correctness).

---

### 2.8 Principle: Normalization of Operational Data

**Statement:** Operational data — employee profiles, organizational structure, earnings configurations, deduction configurations — must be normalized to at least Third Normal Form (3NF). Repeating data groups, transitive dependencies, and derived values must not be stored in operational tables.

**Rationale:** Denormalized operational data creates update anomalies. If an employee's department name is stored directly on the payslip record rather than via a foreign key, renaming the department would create a divergence between the renamed current value and the historical payslip value — unless special logic is applied. Normalization enforces a single source of truth for each data element, with snapshot patterns applied only where immutability of historical state is required.

**Implementation Rule:**

- Each operational entity (Employee, Department, Branch, Earning Type, etc.) is defined once in its own table.
- Foreign keys are used to express relationships; values are never duplicated across tables.
- Derived values (e.g., Total Gross Salary = Basic + Allowances) must not be stored in operational tables; they must only appear in the snapshot/results tables after calculation.
- The exception to normalization is the payroll snapshot / payslip results area, where denormalization is **required** by design principle 2.1 to ensure historical immutability.

**Reference:** `00_SPEC.md` (SOLID principles, Clean Architecture principles); `02_FUNCTIONAL_REQUIREMENTS.md` throughout.

---

### 2.9 Principle: Referential Integrity Enforced at the Database Level

**Statement:** All relationships between tables must be enforced by foreign key constraints declared in the database schema. Application-layer enforcement alone is insufficient.

**Rationale:** Application code can be bypassed — by direct database access, by migrations run in the wrong order, by a data import script, or by a bug. Foreign key constraints at the database level provide an unconditional guarantee of referential integrity regardless of how data enters or leaves the database. For a compliance system, this is non-negotiable.

**Implementation Rule:**

- Every column that references another table's primary key must declare a `FOREIGN KEY` constraint.
- Cascade behaviors must be explicitly defined for every foreign key:
  - `ON DELETE CASCADE` is prohibited for all foreign keys referencing hard record tables (payroll runs, payslips, audit logs).
  - `ON DELETE RESTRICT` is the default for operational data relationships (e.g., an employee cannot be physically deleted if they have payslip records — though soft delete is used, this prevents accidental hard deletes via DB console).
  - `ON DELETE SET NULL` may be used only for genuinely optional relationships where the orphaning of the referencing record is semantically valid (e.g., a nullable `approved_by_user_id` before approval has occurred).
- Nullable foreign key columns must be explicitly documented with the reason the relationship is optional.

**User Deactivation Clarification:** All foreign key columns on financial tables that reference `users.id` — including `submitted_by_user_id` and `approved_by_user_id` on `payroll_runs` — must declare `ON DELETE RESTRICT`. This means a user account that is referenced by any payroll run record (as Maker or Checker) cannot be hard-deleted from the database. Since user accounts use soft delete (see Principle 2.4), deactivating a user sets `deleted_at` on the `users` table and does not trigger the foreign key constraint. However, this rule also means: if a future migration or script attempts a physical `DELETE` on a user record that is referenced by a payroll run, the database engine will refuse it unconditionally. This protects the Maker/Checker audit trail from accidental destruction.

**Reference:** `00_SPEC.md` (Security Requirements — use database transactions for critical operations); `01_BUSINESS_RULES.md` Section 8; `01_BUSINESS_RULES.md` Section 9.3 (Maker/Checker identities must be permanently recorded).

---

## 3. Naming Standards

All database objects — tables, columns, indexes, constraints — must follow the naming conventions defined in this section without exception. Consistency across the entire schema is required to enable reliable code generation, migration tooling, and AI-assisted development.

---

### 3.1 Table Names

| Rule                                | Standard                         | Example                                |
|-------------------------------------|----------------------------------|----------------------------------------|
| Case                                | `snake_case`                     | `payroll_run_employee_results`         |
| Plurality                           | Always plural                    | `employees`, `payroll_runs`            |
| Abbreviations                       | Avoid; use full words            | `employees` not `emps`                 |
| Junction / pivot tables             | Both entity names, alphabetical  | `employee_pay_groups`                  |
| Historical / snapshot tables        | Suffix `_snapshots` or `_history`| `salary_histories`, `paye_band_snapshots` |
| Audit log table                     | `audit_logs`                     |                                        |

---

### 3.2 Column Names

| Rule                                | Standard                         | Example                                |
|-------------------------------------|----------------------------------|----------------------------------------|
| Case                                | `snake_case`                     | `effective_from`, `gross_taxable_salary` |
| Descriptive; no single-letter names | Full words                       | `amount` not `amt`                     |
| Avoid reserved SQL keywords         | Prefix or rephrase               | `period_start` not `start`             |
| Date columns referencing a concept  | Suffix `_at` (datetime) or `_on` / `_date` (date) | `approved_at`, `hire_date` |

---

### 3.3 Primary Keys

| Rule            | Standard                              |
|-----------------|---------------------------------------|
| Column name     | Always `id`                           |
| Type            | Auto-incrementing integer or UUID (defined per project's chosen strategy) |
| Declared on     | Every table without exception         |

---

### 3.4 Foreign Keys

| Rule                                  | Standard                                          | Example                                     |
|---------------------------------------|---------------------------------------------------|---------------------------------------------|
| Column name                           | Singular of referenced table + `_id`              | `employee_id` → references `employees.id`   |
| Polymorphic (where unavoidable)       | `{relation}_type` + `{relation}_id`               | `auditable_type`, `auditable_id`            |
| Self-referential                      | Descriptive prefix + `_id`                        | `parent_department_id`                      |
| User references (Maker/Checker)       | Descriptive role prefix + `_user_id`              | `submitted_by_user_id`, `approved_by_user_id` |

---

### 3.5 Boolean Columns

| Rule                              | Standard                              | Example                                  |
|-----------------------------------|---------------------------------------|------------------------------------------|
| Prefix                            | `is_` or `has_`                       | `is_taxable`, `is_active`, `has_secondary_employment` |
| Default value                     | Always explicitly declared (`TRUE` or `FALSE`) | `is_taxable DEFAULT TRUE`      |
| Never nullable                    | Boolean columns must be `NOT NULL`    |                                          |

---

### 3.6 ENUM Column Values

| Rule                      | Standard                                  | Example                              |
|---------------------------|-------------------------------------------|--------------------------------------|
| Case                      | `snake_case`, lowercase                   | `draft`, `locked`, `leave_without_pay` |
| No abbreviations          | Full descriptive words                    | `approved` not `appr`               |
| Defined in Master Registry | All allowed values listed in Section 4.3 | See below                            |

---

### 3.7 Soft Delete Column

| Rule            | Standard                              |
|-----------------|---------------------------------------|
| Column name     | `deleted_at`                          |
| Type            | `TIMESTAMP NULL`                      |
| Default         | `NULL`                                |
| Presence        | Required on all tables subject to soft delete (see Section 2.4) |

---

### 3.8 Standard Timestamp Columns

Every table must include the following two columns, without exception:

| Column        | Type                  | Description                                                |
|---------------|-----------------------|------------------------------------------------------------|
| `created_at`  | `TIMESTAMP NOT NULL`  | Set automatically at row creation. Never updated.          |
| `updated_at`  | `TIMESTAMP NOT NULL`  | Set automatically at row creation; updated on every `UPDATE`. |

For append-only/hard-record tables (audit logs, payslip line items), `updated_at` is still present in the schema but application-layer policies must ensure it is never modified after initial creation.

---

### 3.9 Index Naming Convention

| Index Type          | Pattern                            | Example                                   |
|---------------------|------------------------------------|-------------------------------------------|
| Standard index      | `idx_{table}_{column(s)}`          | `idx_employees_department_id`             |
| Composite index     | `idx_{table}_{col1}_{col2}`        | `idx_payroll_runs_period_id_status`       |
| Unique constraint   | `uq_{table}_{column(s)}`           | `uq_employees_tin`, `uq_employees_nssf_number` |
| Primary key         | `pk_{table}` (auto-managed by engine) | `pk_employees`                         |
| Foreign key         | `fk_{table}_{referenced_table}`    | `fk_employees_departments`               |

---

### 3.10 Additional Standards

| Concern                           | Standard                                                                 |
|-----------------------------------|--------------------------------------------------------------------------|
| Effective date column (for versioned records) | `effective_from DATE NOT NULL`                               |
| Monetary amount columns           | Suffix `_amount` where the column name alone could be ambiguous          |
| Rate / percentage columns         | Suffix `_rate` or `_percentage` (e.g., `employee_contribution_rate`)     |
| JSON columns (snapshots / audit)  | Suffix `_snapshot` or `_data` (e.g., `calculation_snapshot`, `old_values`) |
| Status columns                    | Named `status` unless multiple status dimensions exist on the same table |
| Notes / comments columns          | Named `notes` or `rejection_reason` (descriptive, not `remarks`)         |

---

## 4. Data Standards

### 4.1 Data Type Standards

The following data types are the canonical standards for every column in the system. Deviations require explicit architectural justification.

| Data Category            | Required Type        | Precision / Notes                                                                                          |
|--------------------------|----------------------|------------------------------------------------------------------------------------------------------------|
| **Money (TZS)**          | `DECIMAL(15,4)`      | 4 decimal places for all internal storage. Never `FLOAT`, `DOUBLE`, or `REAL`. See Section 2.7.           |
| **Percentage / Rate**    | `DECIMAL(8,4)`       | e.g., `0.1000` = 10%, `0.3500` = 35%. Stored as a decimal fraction, not a percentage integer.             |
| **Date only**            | `DATE`               | Used for: hire dates, termination dates, effective_from, period start/end, public holidays.                |
| **Date + Time**          | `TIMESTAMP`          | Used for: created_at, updated_at, deleted_at, approved_at, submitted_at, all audit log timestamps.         |
| **Boolean**              | `BOOLEAN`            | Always `NOT NULL` with an explicit `DEFAULT`. Never `TINYINT(1)`.                                          |
| **Snapshot Data**        | `JSON`               | Immutable payroll calculation snapshots — PAYE bracket used, rates applied. See Principle 2.1.             |
| **Audit Field Values**   | `JSON`               | `old_values` and `new_values` columns in `audit_logs`. Full JSON representation of changed fields.         |
| **Status / Type Fields** | `ENUM`               | All allowed values must be defined in the Master ENUM Registry (Section 4.3). No open-ended string columns for status. |
| **Text (short)**         | `VARCHAR(255)`       | Names, codes, short labels, email addresses.                                                               |
| **Text (medium)**        | `VARCHAR(1000)`      | Descriptions, notes, rejection reasons.                                                                    |
| **Text (long / free)**   | `TEXT`               | Documents, long-form notes, serialized data not suited to JSON.                                            |
| **Identifiers / Codes**  | `VARCHAR(50)`        | Employee Number, TIN, NSSF Number, Bank Account Number, Branch Code.                                       |
| **IP Address**           | `VARCHAR(45)`        | Supports IPv4 and IPv6 in `audit_logs`.                                                                    |

---

### 4.2 Global System Standards

| Concern                  | Standard                      | Source                                              |
|--------------------------|-------------------------------|-----------------------------------------------------|
| **Currency**             | Tanzanian Shilling (TZS)      | `01_BUSINESS_RULES.md` Section 7                   |
| **Timezone**             | `Africa/Dar_es_Salaam`        | All `TIMESTAMP` values stored and interpreted in EAT (UTC+3). |
| **Character Encoding**   | `UTF-8`                       | Supports Swahili names, special characters, multilingual data. |
| **Rounding Rule**        | Half-up arithmetic rounding   | `01_BUSINESS_RULES.md` Section 7.                  |
| **Internal Precision**   | `DECIMAL(15,4)` — 4 decimal places retained throughout all intermediate calculation storage. | `01_BUSINESS_RULES.md` Section 7. |
| **Output Precision**     | 0 decimal places on final Net Salary in UI, reports, and bank export. Derived from the `DECIMAL(15,4)` stored value at output time. | `01_BUSINESS_RULES.md` Section 7. |
| **Rounding Residual**    | Stored as a distinct `rounding_adjustment DECIMAL(15,4)` column on payslip result records. | `01_BUSINESS_RULES.md` Section 7; `02_FUNCTIONAL_REQUIREMENTS.md` FR-REP-001 Acceptance Criteria. |
| **NULL Policy**          | Columns must be `NOT NULL` by default unless the absence of a value is semantically meaningful and explicitly documented. **Example:** `approved_by_user_id` is `NULL` until the Checker approves a run — this is semantically valid and expected. `employee_id` on a payroll result record is never `NULL` — a NULL here would represent a data integrity failure and must be enforced as `NOT NULL`. | `00_SPEC.md` (clean architecture). |
| **Audit Retention**      | No financial or audit record is ever physically deleted. Minimum 5-year floor (Income Tax Act). Internal policy: 7–10 years. | `01_BUSINESS_RULES.md` Section 10. |

---

### 4.3 Master ENUM Registry

Every column in the system that uses an `ENUM` type must draw its allowed values exclusively from this registry. No ad-hoc ENUM values are permitted. If a new status or type is required, this registry must be updated first, before any schema change is made.

---

#### `employee_status`

The employment status of an employee record.

| Value        | Meaning                                                    |
|--------------|------------------------------------------------------------|
| `active`     | Currently employed; eligible for inclusion in payroll runs. |
| `terminated` | Employment ended. Excluded from new payroll periods opened after termination date. |

> **Scope Note:** Only `active` and `terminated` are defined for the MVP. Values such as `on_leave` or `suspended` are not supported by `02_FUNCTIONAL_REQUIREMENTS.md` and must not be implemented or referenced until explicitly added to the FRD. Adding them now would cause the system to build features — UI states, filtering logic, payroll exclusion rules — that have no specified behaviour.

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-EMP-009, FR-EMP-010.

---

#### `payroll_run_status`

The lifecycle state of a payroll run. This ENUM exactly mirrors the state machine defined in `01_BUSINESS_RULES.md` Section 9.1. No additional states may be introduced without a corresponding state machine revision.

| Value       | Meaning                                                                         |
|-------------|---------------------------------------------------------------------------------|
| `draft`     | Run created and employees loaded. Editable. Exports forbidden.                  |
| `validated` | Pre-flight checks passed. Ready for calculation. Calculation not yet executed.  |
| `preview`   | Calculations complete. Under review by Maker. Regeneration allowed.             |
| `approved`  | Submitted by Maker; awaiting Checker decision. No calculation changes permitted.|
| `locked`    | Approved by Checker. Immutable. Bank export and statutory reports available.    |
| `filed`     | Finalized and exported to TRA/NSSF. Voiding forbidden. Amended Return required. |
| `amended`   | Correction run linked to a Filed original run. Corrections and delta calculation allowed. Forbidden: adding employees not in the original run. Transitions to `filed` once remitted. |
| `reversed`  | A Locked (non-Filed) run that has been administratively voided.                 |

> **Confirmation:** The `amended` state is explicitly defined in `01_BUSINESS_RULES.md` Section 9.1 (State Machine narrative, line for "Amended") and in the State Transition Table (row: `Filed → Initiate Correction → Admin → Amended`; row: `Amended → Mark as Filed → Checker/Admin → Filed`). It is a valid, spec-defined state and must be retained in this ENUM.

**Reference:** `01_BUSINESS_RULES.md` Section 9.1 (State Transition Table); `02_FUNCTIONAL_REQUIREMENTS.md` FR-PROC-008, FR-PROC-014.

---

#### `leave_type`

The category of a leave event.

| Value                 | Payroll Impact                                                          |
|-----------------------|-------------------------------------------------------------------------|
| `annual`              | No reduction to Basic Salary. HR tracking only.                         |
| `sick`                | No reduction to Basic Salary. HR tracking only.                         |
| `maternity`           | No reduction to Basic Salary. HR tracking only.                         |
| `paternity`           | No reduction to Basic Salary. HR tracking only.                         |
| `unpaid`              | Reduces Basic Salary proportionally. Passed to payroll engine.          |
| `unauthorized_absence`| Treated identically to `unpaid` by the payroll engine. Deducts from Basic Salary. |

**Reference:** `01_BUSINESS_RULES.md` Section 6 (Paid and Unpaid Leave rules); `02_FUNCTIONAL_REQUIREMENTS.md` FR-LEAV-001, FR-LEAV-005.

---

#### `earning_recurrence`

Whether an earning is applied in every payroll period or only once.

| Value       | Meaning                                                                |
|-------------|------------------------------------------------------------------------|
| `recurring` | Applied automatically to every payroll period until explicitly removed. |
| `one_time`  | Applied only to the specific open period in which it was entered.      |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-EARN-004, FR-EARN-006.

---

#### `deduction_basis`

The calculation basis for a deduction amount.

| Value              | Meaning                                                             |
|--------------------|---------------------------------------------------------------------|
| `fixed_amount`     | A specific monetary value deducted regardless of salary.            |
| `percentage_basic` | A percentage applied against the employee's Basic Salary at runtime.|

**Reference:** `01_BUSINESS_RULES.md` Section 5 (Optional Deductions); `02_FUNCTIONAL_REQUIREMENTS.md` FR-DED-004.

---

#### `loan_status`

The lifecycle state of an employee loan.

| Value       | Meaning                                                                     |
|-------------|-----------------------------------------------------------------------------|
| `active`    | Loan is ongoing; installment deductions are generated each period.          |
| `suspended` | Deductions temporarily paused for one or more periods.                      |
| `completed` | Outstanding balance has reached zero; no further deductions.                |
| `closed`    | Manually closed by Finance before full repayment (e.g., written off or restructured). |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-LOAN-004, FR-LOAN-005; `01_BUSINESS_RULES.md` Section 5.

---

#### `employment_type`

The contractual nature of the employment relationship.

| Value         | Meaning                                      |
|---------------|----------------------------------------------|
| `permanent`   | Indefinite contract.                         |
| `contract`    | Fixed-term contract with a defined end date. |
| `casual`      | Day-to-day or hourly engagement.             |

> **Scope Note:** `probation` has been removed. It is not defined in `02_FUNCTIONAL_REQUIREMENTS.md` FR-EMP-003 or anywhere in the governing spec documents. It was generated speculatively. For MVP, employment type reflects the contractual category only. Probation handling, if required, is a Phase 2 concern that must first be defined in the FRD before being added here.

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-EMP-003.

---

#### `resident_status`

The tax residency classification of an employee, which determines the PAYE calculation method applied.

| Value          | PAYE Treatment                                                                   |
|----------------|----------------------------------------------------------------------------------|
| `resident`     | Progressive stacked PAYE brackets applied to Taxable Income.                     |
| `non_resident` | Flat tax rate applied, sourced from the statutory configuration table. Never hardcoded. |

**Reference:** `01_BUSINESS_RULES.md` Section 2 (Non-Residents); `02_FUNCTIONAL_REQUIREMENTS.md` FR-STAT-005.

---

#### `deduction_priority_tier`

The priority tier that governs the order in which post-tax deductions are applied against the Net Pool. This ENUM exactly mirrors the Canonical 5-Tier Order defined in `01_BUSINESS_RULES.md` Section 5.

| Value        | Tier | Deduction Category                                    |
|--------------|------|-------------------------------------------------------|
| `tier_1`     | 1    | Statutory deductions (NSSF, PAYE — applied pre-net-pool) |
| `tier_2`     | 2    | Court orders and legally binding attachments.         |
| `tier_3`     | 3    | Company loans and salary advances.                    |
| `tier_4`     | 4    | Third-party loans (SACCOs, banks).                    |
| `tier_5`     | 5    | Voluntary deductions (union, welfare, insurance).     |

**Reference:** `01_BUSINESS_RULES.md` Section 5 (Deduction Priority Order); `02_FUNCTIONAL_REQUIREMENTS.md` FR-DED-001.

> **Note on Tier 1:** NSSF and PAYE are applied by the calculation engine during Stages A and B of the calculation pipeline (see `01_BUSINESS_RULES.md` Section 1). They are not applied as post-net-pool deductions in the same programmatic sense as Tiers 2–5. The `tier_1` value in this ENUM is used for classification and display purposes on payslips only.

---

#### `overtime_type`

Distinguishes how an overtime entry's monetary value is determined. Required because the overtime records table must know whether to apply the hours-based formula or use the entered amount directly.

| Value          | Meaning                                                                                       |
|----------------|-----------------------------------------------------------------------------------------------|
| `hours_based`  | Overtime amount is calculated at runtime using the formula defined in `01_BUSINESS_RULES.md` Section 4: `(Basic Salary / Standard Monthly Hours) × Overtime Rate × Overtime Hours`. |
| `fixed_amount` | HR has entered a direct TZS monetary amount. No formula is applied; the stored amount is used as-is. |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-ATT-001 (hours-based input), FR-ATT-002 (fixed amount input); `01_BUSINESS_RULES.md` Section 4 (Overtime formula).

---

#### `payroll_run_type`

Distinguishes the purpose and scope of a payroll run. The `payroll_runs` table must store this to determine which validation rules, employee scope constraints, and export file formats apply.

| Value             | Meaning                                                                                                          |
|-------------------|------------------------------------------------------------------------------------------------------------------|
| `standard`        | The regular monthly payroll run covering all active employees in the period.                                     |
| `supplementary`   | An off-cycle run within the same period, covering only a specific subset of employees — typically those who failed processing in the standard run. A period may have at most one Locked standard run before a supplementary run is opened. |
| `amended_return`  | A correction run linked to a specific Filed standard or supplementary run. Scope is restricted to employees present in the original run. Used exclusively for the Amended Return Workflow. |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-PROC-013 (supplementary run), FR-PROC-014 (amended return); `01_BUSINESS_RULES.md` Section 9.1 (`Amended` state definition).

---

#### `notification_event_type`

The system event that triggered a notification dispatch.

| Value                         | Trigger Description                                            |
|-------------------------------|----------------------------------------------------------------|
| `password_reset_requested`    | User requests a password reset link.                           |
| `payroll_submitted`           | Maker submits a payroll run for Checker approval.              |
| `payroll_approved`            | Checker approves and locks a payroll run.                      |
| `payroll_rejected`            | Checker rejects a submitted payroll run.                       |
| `payslip_available`           | Payroll run is locked; employee payslips are accessible.       |
| `loan_registered`             | A new loan is registered against an employee.                  |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-NOTIF-001 through FR-NOTIF-006; Business Events Registry (Section 8).

---

#### `user_role`

System roles used for role-based access control.

> **Implementation Note:** As of `05_SYSTEM_ARCHITECTURE.md` §3.3, this ENUM is not implemented as a database column type. It is the authoritative seed list for `spatie/laravel-permission`'s `roles` table. The package's `model_has_roles` pivot replaces the `user_roles` table previously specified in §5.1.

| Value                 | Meaning                                                  |
|-----------------------|----------------------------------------------------------|
| `system_administrator`| Full system access and configuration.                    |
| `hr_manager`          | Full access to HR and employee records.                  |
| `hr_officer`          | Operational HR access.                                   |
| `payroll_officer`     | Operational payroll access.                              |
| `finance_manager`     | Full access to finance and payroll configurations.       |
| `finance_officer`     | Operational finance access.                              |
| `department_manager`  | Read-only access scoped to their department.             |
| `employee`            | Self-service access (e.g., payslips, leave requests).    |
| `auditor`             | Read-only access to all records and audit logs.          |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` Section 3.1.

---

#### `processing_status`

The calculation state of an individual employee within a payroll run.

| Value                         | Meaning                                                                 |
|-------------------------------|-------------------------------------------------------------------------|
| `pending`                     | Queued for calculation.                                                 |
| `calculated`                  | Successfully calculated.                                                |
| `failed`                      | Processing failed due to an error (e.g., missing mandatory data).       |
| `flagged_insufficient_funds`  | Calculated, but net pay falls below statutory or policy minimums.       |

**Reference:** `02_FUNCTIONAL_REQUIREMENTS.md` FR-PROC-007, Module 4.12 Alternative Flows.

---

#### `payroll_period_status`

The lifecycle state of a payroll period.

| Value       | Meaning                                                                        |
|-------------|--------------------------------------------------------------------------------|
| `open`      | The active, current period accepting operational data.                         |
| `closed`    | A completed period whose standard payroll run has been locked and filed.       |

**Reference:** `01_BUSINESS_RULES.md` Section 9.2 (Payroll Period Rules).

---

#### `statutory_code`

Identifies the specific statutory configuration record.

| Value            |
|------------------|
| `nssf_employer`  |
| `nssf_employee`  |
| `wcf`            |
| `sdl`            |

**Reference:** `01_BUSINESS_RULES.md` Section 1, Section 5.

---

#### `audit_event_type`

The category of action recorded in an audit log entry.

| Value               | Meaning                                                                    |
|---------------------|----------------------------------------------------------------------------|
| `created`           | A new record was created.                                                  |
| `updated`           | An existing record was modified.                                           |
| `deleted`           | A record was soft-deleted (logically removed).                             |
| `restored`          | A soft-deleted record was restored.                                        |
| `login_success`     | A user successfully authenticated.                                         |
| `login_failed`      | An authentication attempt failed.                                          |
| `logout`            | A user session was terminated.                                             |
| `password_changed`  | A user's password was updated.                                             |
| `state_transition`  | A payroll run changed state (e.g., draft → validated, approved → locked).  |
| `admin_override`    | A System Administrator performed an override action (e.g., period reopened). |
| `export_generated`  | A bank export file or statutory report was generated and downloaded.        |

**Reference:** `01_BUSINESS_RULES.md` Section 8 (Audit Trail); `02_FUNCTIONAL_REQUIREMENTS.md` FR-AUD-001 through FR-AUD-006.

---

#### `issue_level`

Severity of an issue found during pre-flight validation.

| Value       | Meaning                                                                    |
|-------------|----------------------------------------------------------------------------|
| `warning`   | Anomalies that do not block the run, but should be reviewed.               |
| `error`     | Critical issues that block calculation until resolved.                     |

**Reference:** `01_BUSINESS_RULES.md` Section 9.5.

---

#### `notification_status`

The dispatch state of a system notification.

| Value       | Meaning                                                                    |
|-------------|----------------------------------------------------------------------------|
| `pending`   | Notification is queued for dispatch.                                       |
| `sent`      | Notification was successfully dispatched to the external provider (SMTP).  |
| `failed`    | Notification dispatch failed after all retries.                            |

---

#### `line_item_type`

Categorizes a financial entry on an employee's payslip.

| Value       | Meaning                                                                    |
|-------------|----------------------------------------------------------------------------|
| `earning`   | A positive monetary addition to the employee's gross pay.                  |
| `deduction` | A negative monetary deduction from the employee's net pool.                |

---

---

## 5. Entity Catalog

This section defines the core tables that form the schema, grouped by business domain. It identifies the table's purpose, its classification (Operational vs. Hard Record), and the critical columns that implement the design principles defined in Section 2.

> **Note on Standard Columns:** As per Section 3, every table implicitly includes `id` (Primary Key), `created_at`, and `updated_at`. Tables subject to soft delete implicitly include `deleted_at`. These are omitted from the lists below for brevity unless they require special context.

### 5.1 Identity & Access Control

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `users` | Operational (Soft Delete) | Stores user accounts. | `email` (Unique), `password_hash`. **Note:** Deactivating a user sets `deleted_at`, but FKs from financial tables must use `ON DELETE RESTRICT` to prevent physical removal. |
| `user_roles` | **SUPERSEDED** — not implemented | Roles are managed by `spatie/laravel-permission`'s own `roles` and `model_has_roles` tables, seeded with exactly the 9 values from the `user_role` ENUM below. See `05_SYSTEM_ARCHITECTURE.md` §3.3. This row is retained only so the `user_role` ENUM (§4.3) still has a documented purpose as the package's seed list. |
| `user_department_scopes` | Operational (Soft Delete) | Scopes a user's visibility to specific departments. | `user_id`, `department_id`. Used primarily for the `department_manager` role. **Note:** Deletions of scope must be captured by audit logging. |
| `password_reset_tokens` | Operational | Stores temporary tokens for password resets. | `email`, `token`, `expires_at` (`TIMESTAMP`). |

### 5.2 Organizational Structure

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `company_profile` | Operational | Single-row table storing core company metadata. | `name`, `tin`, `address`, `registration_number`, `working_days_per_month` (`INT`), `financial_year_start_month` (`INT`), `sdl_enabled` (`BOOL`), `wcf_enabled` (`BOOL`), `sdl_employee_threshold` (`INT`). **Constraint:** Must enforce a single row either via `CHECK (id = 1)` or application-layer guards. |
| `system_settings` | Operational | Key-value store for global configurations. | `key` (`VARCHAR`, Unique), `value` (`TEXT`), `updated_by_user_id`. |
| `branches` | Operational (Soft Delete) | Physical or logical branch locations. | `code` (Unique), `name`. |
| `departments` | Operational (Soft Delete) | Departments within the organization. | `code` (Unique), `name`, `branch_id` (FK, NOT NULL). |
| `cost_centers` | Operational (Soft Delete) | Accounting cost centers for GL mapping. | `code` (Unique), `name`, `branch_id` (Nullable FK), `department_id` (Nullable FK). **Constraint**: At least one FK must be non-null. |

### 5.3 Employee Lifecycle

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `banks` | Operational | Lookup table for commercial banks. | `code` (Unique), `name`. |
| `employees` | Operational (Soft Delete) | The master employee profile. | `employee_number` (Unique), `status` (ENUM: `employee_status`), `employment_type` (ENUM: `employment_type`), `resident_status` (ENUM: `resident_status`), `secondary_employment_flag` (`BOOL`), `job_title` (`VARCHAR`), `hire_date`, `termination_date`, `department_id`, `branch_id`, `tin`. |
| `employee_bank_details` | Operational (Soft Delete) | Tracks employee bank accounts. | `employee_id`, `bank_id`, `branch_code`, `account_number`, `is_primary` (`BOOL`). |
| `employee_scheme_enrollments` | **Hard Record** (Effective-Dated) | Tracks statutory scheme memberships. | `employee_id`, `scheme_code` (ENUM: `statutory_code`), `membership_number`, `effective_from` (`DATE`). |
| `emergency_contacts` | Operational | Stores employee emergency contacts. | `employee_id`, `name`, `relationship`, `phone_number`. |
| `employee_documents` | Operational (Soft Delete) | Attachments (contracts, IDs) for an employee. | `employee_id`, `document_type` (`VARCHAR`), `file_name`, `file_path`, `file_hash` (`VARCHAR`), `uploaded_by_user_id`. **Note:** Integrity depends on external file storage remaining intact; `file_path` alone is insufficient for compliance without a corresponding `file_hash`. |

### 5.4 Salary Structure & History

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `salary_structures` | Operational (Soft Delete) | Defines standard salary grades/scales. | `code` (Unique), `name`. |
| `salary_histories` | **Hard Record** (Append-Only) | Tracks all historical Basic Salary changes. | `employee_id`, `salary_structure_id` (Nullable), `basic_salary_amount` (`DECIMAL(15,4)`), `effective_from` (`DATE`). **Note:** Never updated. No `deleted_at`. (See Principle 2.3). |

### 5.5 Earnings Configuration

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `earning_types` | Operational (Soft Delete) | Defines categories of allowances/bonuses. | `code`, `name`, `is_taxable` (`BOOL`), `is_pensionable` (`BOOL`), `recurrence` (ENUM: `earning_recurrence`). |
| `employee_earnings` | Operational | Assigns earnings to specific employees. | `employee_id`, `earning_type_id`, `amount` (`DECIMAL(15,4)`), `payroll_period_id` (Nullable. Required if `one_time`), `is_active` (`BOOL`). **Note:** Conditional nullability for `payroll_period_id` must be enforced at the application layer via validation rules. |

### 5.6 Deductions Configuration

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `deduction_types` | Operational (Soft Delete) | Defines categories of deductions. | `code`, `name`, `basis` (ENUM: `deduction_basis`), `default_amount` (`DECIMAL(15,4)`), `default_percentage` (`DECIMAL(8,4)`), `priority_tier` (ENUM: `deduction_priority_tier`). |
| `employee_deductions` | Operational | Assigns deductions to specific employees. | `employee_id`, `deduction_type_id`, `amount` (`DECIMAL(15,4)`), `percentage` (`DECIMAL(8,4)`), `payroll_period_id` (Nullable. Required if one-time), `is_active` (`BOOL`). |

### 5.7 Statutory Rate Configuration

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `statutory_configurations` | **Hard Record** (Effective-Dated) | Flat statutory rates (e.g., WCF, SDL, NSSF). | `code` (ENUM: `statutory_code`), `rate_percentage` (`DECIMAL(8,4)`), `threshold_amount` (`DECIMAL(15,4)`), `minimum_headcount` (`INT`), `effective_from` (`DATE`). (See Principle 2.2). **Note on WCF:** WCF rate varies by industry (e.g., private sector 0.6%, financial 0.5%). This is handled globally here as MVP assumes single industry/company. |
| `paye_brackets` | **Hard Record** (Effective-Dated) | Progressive PAYE tax brackets. | `minimum_income` (`DECIMAL(15,4)`), `maximum_income` (`DECIMAL(15,4)`, Nullable), `rate_percentage` (`DECIMAL(8,4)`), `base_tax_amount` (`DECIMAL(15,4)`), `effective_from` (`DATE`). **Note:** The highest bracket has no upper limit, so `maximum_income` must be `NULL` for that row. |

### 5.8 Leave & Attendance

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `public_holidays` | Operational | Global list of public holidays. | `date` (Unique), `name`. Used by working days calculation. |
| `leave_records` | Operational (Soft Delete) | Tracks employee leave periods. | `employee_id`, `leave_type` (ENUM: `leave_type`), `start_date`, `end_date`, `total_days`. **Note:** No approval status; HR records leave directly for MVP. |
| `overtime_records` | Operational (Soft Delete) | Tracks overtime entered for payroll. | `employee_id`, `payroll_period_id`, `overtime_type` (ENUM: `overtime_type`), `hours` (`DECIMAL(8,2)`), `overtime_rate_multiplier` (`DECIMAL(8,4)`), `fixed_amount` (`DECIMAL(15,4)`), `approved_by_user_id`. |

### 5.9 Loan Management

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `loans` | Operational (Soft Delete) | Tracks principal and status of company loans. | `employee_id`, `principal_amount` (`DECIMAL(15,4)`), `installment_amount` (`DECIMAL(15,4)`), `total_repaid_amount` (`DECIMAL(15,4)`), `loan_status` (ENUM: `loan_status`). |
| `loan_installments` | Hard Record | Maps deductions to specific periods (immutable once the associated payroll_run reaches locked). | `loan_id`, `payroll_period_id`, `amount_deducted` (`DECIMAL(15,4)`), `outstanding_balance_before` (`DECIMAL(15,4)`), `outstanding_balance_after` (`DECIMAL(15,4)`). |

### 5.10 Payroll Period Governance

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `payroll_periods` | Operational | Defines the monthly payroll cycles. | `year`, `month`, `start_date`, `end_date`, `status` (ENUM: `payroll_period_status`). **Note:** Only one period may be `open` at any time. Reopening a closed period sets it back to `open` (the `reopened` action translates to `open` state). |

### 5.11 Payroll Runs & Snapshots (The Core Engine)

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `payroll_runs` | **Hard Record** | Header record for a payroll execution. | `payroll_period_id`, `payroll_run_type` (ENUM: `payroll_run_type`), `status` (ENUM: `payroll_run_status`), `original_run_id` (Nullable FK for Amended Runs), `reversed_by_run_id` (Nullable FK), `reversed_at` (`TIMESTAMP`), `submitted_by_user_id`, `approved_by_user_id`, `total_gross_amount`, `total_net_amount` (`DECIMAL(15,4)`). (See Principles 2.5, 2.6). |
| `payroll_run_employee_scope`| **Hard Record** | Defines which employees are included in a specific run. | `payroll_run_id`, `employee_id`. Used heavily for supplementary and amended runs. |
| `payroll_preflight_results` | **Hard Record** | Stores warnings and errors generated during Validation. | `payroll_run_id`, `employee_id` (Nullable), `issue_level` (ENUM: `issue_level`), `issue_code`, `description`. |
| `payroll_run_results` | Operational (Transient before Lock) / **Hard Record** (After Lock) | Point-in-time snapshot of employee payslip totals. | `payroll_run_id`, `employee_id`, `processing_status` (ENUM: `processing_status`), `basic_salary_amount`, `gross_salary_amount`, `taxable_income_amount`, `paye_tax_amount`, `nssf_deduction_amount`, `total_deductions_amount`, `net_salary_amount`, `rounding_adjustment` (All `DECIMAL(15,4)`). `calculation_snapshot` (`JSON`). **Note:** Each recalculation deletes and rewrites results while in Draft/Preview state (making them Operational until locked). The Approved→Locked transition makes them permanent Hard Records. |
| `payslip_line_items` | **Hard Record** | Individual earnings/deductions applied on the payslip. | `payroll_run_result_id`, `type` (ENUM: `line_item_type`), `code`, `name`, `amount` (`DECIMAL(15,4)`). Allows reconstruction of the exact payslip layout. |
| `payroll_arrears_workings` | **Hard Record** | Tracks detailed calculations for arrears and backpay. | `payroll_run_result_id`, `source_period_id` (FK to `payroll_periods.id`), `earning_type_id`, `expected_amount`, `paid_amount`, `arrears_amount` (`DECIMAL(15,4)`). |
| `payroll_run_state_logs` | **Hard Record** (Append-Only) | Logs all state transitions and rejections. | `payroll_run_id`, `from_status` (ENUM: `payroll_run_status`), `to_status` (ENUM: `payroll_run_status`), `actioned_by_user_id`, `rejection_reason` (`TEXT`). (See Principle 2.6). |

### 5.12 Bank Export Tracking

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `bank_exports` | **Hard Record** | Logs the generation of bank CSVs. | `payroll_run_id`, `generated_by_user_id`, `file_hash` (SHA-256 for file integrity), `total_records`, `total_amount` (`DECIMAL(15,4)`). |

### 5.13 System & Audit

| Table | Classification | Purpose | Critical Columns / Design Notes |
|-------|----------------|---------|---------------------------------|
| `audit_logs` | **Hard Record** (Append-Only) | Immutable ledger of all system actions. | `user_id`, `event_type` (ENUM: `audit_event_type`), `table_name`, `record_id`, `old_values` (`JSON`), `new_values` (`JSON`), `ip_address`. |
| `notifications` | Operational (Soft Delete) | Tracks system notifications sent. | `user_id`, `event_type` (ENUM: `notification_event_type`), `subject`, `body`, `status` (ENUM: `notification_status`), `retry_count` (`INT`). **Note:** `is_read` is omitted as MVP is email-only. |

---

## 6. Relationships

The system strictly enforces referential integrity through database-level foreign key constraints. This section highlights the structural relationships between the core entities.

### 6.1 Foundational Relationships (Organization & Employees)
- **Employee to Organization:** An `Employee` belongs to exactly one `Department` and one `Branch`. (One-to-Many).
- **Employee to Bank:** An `Employee` has one or many `Bank Detail` records via `employee_bank_details`. The active payment account is identified by `is_primary = TRUE`. Only one record per employee may have `is_primary = TRUE` at a time.
- **Employee to Salary History:** An `Employee` has one or many `Salary History` records. The active salary is resolved via the `effective_from` date. (One-to-Many).

### 6.2 Configuration & Assignments
- **Employee to Earnings:** An `Employee` has many `Employee Earnings`, which reference exactly one `Earning Type`. (Many-to-Many resolved via `employee_earnings`).
- **Employee to Deductions:** An `Employee` has many `Employee Deductions`, which reference exactly one `Deduction Type`. (Many-to-Many resolved via `employee_deductions`).
- **Employee to Loans:** An `Employee` has zero or many `Loans`. A `Loan` is linked to multiple `Loan Installments` across different periods.

### 6.3 Payroll Processing Pipeline
- **Period to Runs:** A `Payroll Period` may have multiple `Payroll Runs` (e.g., one standard, one supplementary, one amended return).
- **Run to Results:** A `Payroll Run` acts as the header for many `Payroll Run Results` (one result per employee processed in that run).
- **Result to Line Items:** A `Payroll Run Result` acts as the header for many `Payslip Line Items`. Every earning, allowance, deduction, and loan installment processed by the engine for that employee is written here as a permanent, denormalized snapshot.
- **Run to Audit/Export:** A `Payroll Run` is the parent entity for `Bank Exports` and is heavily referenced in the `Audit Logs`.

### 6.4 Maker/Checker Constraints
- **User to Run (Maker):** A `User` (Maker) submits many `Payroll Runs`. (`users.id` ← `payroll_runs.submitted_by_user_id`).
- **User to Run (Checker):** A `User` (Checker) approves many `Payroll Runs`. (`users.id` ← `payroll_runs.approved_by_user_id`).
- **Transaction Rule:** The database transaction must fail if `submitted_by_user_id = approved_by_user_id`. (See Principle 2.6).

---

## 7. Constraints

To guarantee data integrity beyond application-layer validation, the database must enforce the following structural constraints.

### 7.1 Unique Constraints
Unique constraints must account for soft deletes. If a record is logically deleted, its unique identifier (e.g., email or employee number) should become available for re-use.
- **Implementation:** Depending on the SQL dialect, this is achieved via partial unique indexes (e.g., `WHERE deleted_at IS NULL`) or by including the `deleted_at` column in the composite unique key.
- **Required Unique Fields:**
  - `users.email`
  - `employees.employee_number`
  - `employees.tin` — **Partial index:** `UNIQUE (tin) WHERE deleted_at IS NULL`. A terminated employee's TIN must not block re-hire under a new record. Uniqueness is only enforced among active (non-deleted) employees, per FR-EMP-002.
  - `departments.code`, `branches.code`, `cost_centers.code`
  - `salary_structures.code`
  - `earning_types.code`, `deduction_types.code`
  - `employee_scheme_enrollments` composite unique `(employee_id, scheme_code, membership_number)` (Partial index where `deleted_at IS NULL`)

### 7.2 Check Constraints
- **Positive Monetary Values:** All `amount` columns across earnings, deductions, salary, and loan tables must have a `CHECK (amount >= 0)` constraint. Payroll mathematics handles subtractions natively; we do not store negative numbers.
- **Maker/Checker Segregation:** The `payroll_runs` table must enforce `CHECK (submitted_by_user_id != approved_by_user_id)`. Note: SQL CHECK constraints evaluating multiple columns handle NULLs gracefully (the check passes if either is NULL, which is valid before approval).
- **Date Sequences:** Any table with a start and end date (e.g., `payroll_periods`, `leave_records`) must enforce `CHECK (end_date >= start_date)`.

### 7.3 Foreign Key Restrict Rules
As defined in Principle 2.9 and Section 5.1, `ON DELETE CASCADE` is strictly prohibited for financial, snapshot, and audit tables.
- `ON DELETE RESTRICT` must be applied to all `user_id`, `employee_id`, and `payroll_period_id` references on transactional tables to prevent accidental physical data loss.

---

## 8. Snapshot Strategy

The snapshot strategy enforces Principle 2.1 (Immutable Payroll Snapshots).

### 8.1 The Denormalized Payslip Line Item
When a payroll run calculates, the engine does not link `payslip_line_items` to `earning_types.id` or `deduction_types.id` via foreign keys. Doing so would risk the line item becoming orphaned if the configuration was later soft-deleted.
- Instead, the engine **copies** the `code` and `name` from the configuration table into the `payslip_line_items` record at the exact moment of calculation.
- This creates a permanent, self-contained record of what the earning/deduction was called at that point in time.

### 8.2 The Calculation Snapshot (JSON)
The `payroll_run_results` table contains a `calculation_snapshot` JSON column. This must store the exact statutory rates, PAYE brackets, and logic parameters used to arrive at the net pay.
- If TRA audits the system 4 years later and asks why 45,000 TZS was deducted for PAYE, the JSON snapshot provides the exact tax bracket definition applied that month, proving compliance even if the current tax brackets have changed.

---

## 9. Effective Dating Strategy

The effective dating strategy enforces Principle 2.2 and 2.3. It applies to `salary_histories`, `statutory_configurations`, and `paye_brackets`.

### 9.1 Resolution Query Pattern
To find the active configuration or salary for a given payroll period, the system must not rely on an `is_active` boolean, as this fails for backdated or retroactive runs. The canonical query pattern is:

```sql
SELECT * FROM {table}
WHERE employee_id = ? -- (if applicable)
  AND effective_from <= ? -- (The end_date of the payroll period)
ORDER BY effective_from DESC
LIMIT 1;
```

### 9.2 Future Dating
This strategy natively supports future-dated changes. HR can enter a salary increase in June that takes effect on September 1st. The August payroll run will automatically pick up the older record, and the September run will automatically pick up the new one, with no manual intervention required at month-end.

---

## 10. Audit Strategy

The audit strategy guarantees Traceability (Design Goal 4).

### 10.1 Application-Layer Observers
The system will implement auditing via the application layer (e.g., ORM Observers or Event Listeners). Every Create, Update, Delete, and Restore action on an operational entity must automatically trigger an insert into the `audit_logs` table.

### 10.2 JSON Payload Structure
The `old_values` and `new_values` JSON columns must only store the fields that actually changed (the delta), plus the primary key. Storing the entire model payload for every minor update unnecessarily inflates database size.

### 10.3 Structural Protection
To compensate for the audit log being written by the application layer, the database layer must guarantee its survival. The `audit_logs` table is a Hard Record table. Database users must be denied `UPDATE` and `DELETE` privileges on this table.

---

## 11. Indexing Strategy

To maintain high performance during bulk payroll calculation and reporting, indexes must be applied deliberately.

### 11.1 Mandatory Indexes
- **Primary Keys:** Auto-indexed by the database engine.
- **Foreign Keys:** Every foreign key column must have a standard B-Tree index.

### 11.2 Search & Lookup Indexes
- **Unique Identifiers:** `tin`, `nssf_number`, `employee_number`, `email` must be indexed to support fast exact-match lookups.
- **Status Columns:** `status` columns on high-volume tables (e.g., `employees`) should be indexed to quickly filter active vs. terminated records.

### 11.3 Composite Indexes for Effective Dating
Tables utilizing the effective dating strategy require composite indexes to optimize the resolution query (Section 9.1).
- `salary_histories`: `INDEX (employee_id, effective_from DESC)`
- `statutory_configurations`: `INDEX (code, effective_from DESC)`

---

## 12. Performance Strategy

Payroll processing is a batch-heavy operation. The database design supports performance through the following paradigms:

### 12.1 N+1 Prevention
The schema relies heavily on normalized data. The application layer must utilize eager loading when fetching employee records for the calculation engine. The database is designed to allow the engine to fetch all active employees, their active salary, and their active earnings/deductions in a minimal number of batch queries.

### 12.2 Decimal Arithmetic
All monetary mathematics must be executed in the application layer using arbitrary-precision math libraries (e.g., BCMath) or inside the database using exact `DECIMAL` aggregation. The database schema guarantees no floating-point pollution will occur.

---

## 13. Archiving Strategy

### 13.1 Retention Policy
By law (Tanzania Income Tax Act) and internal policy, no financial, payroll, or audit data may be destroyed within 10 years of creation.

### 13.2 MVP Implementation
For the MVP, there is no physical archiving (moving data to cold storage). The schema is designed to hold all historical data inline permanently. Hard Records are retained implicitly. Operational records are retained via Soft Deletes. Application queries must always filter by `deleted_at IS NULL` for active operations, naturally archiving old records from the active UI while preserving them for reports.

---

## 14. Backup Considerations

Because this database is a compliance artefact, disaster recovery is paramount.

- **Point-in-Time Recovery (PITR):** The PostgreSQL server must be configured with WAL archiving (`wal_level=replica` or higher) to enable restoring the database to any specific point in the past 7–30 days.
- **Immutability Protection:** Database backups must be shipped to off-site, read-only storage (e.g., AWS S3 with Object Lock, Supabase PITR, or equivalent object storage with immutability guarantees) to prevent tampering with historical audit logs even if the primary database server is compromised. For MVP hosted on Supabase, PITR and daily backups are provided by the platform. Self-hosted deployments must configure `pg_basebackup`, Barman, or pgBackRest.