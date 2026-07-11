# 05_SYSTEM_ARCHITECTURE.md
# System Architecture Document
## Tanzanian Payroll & HR Management System

---

| Attribute  | Value                                                                             |
|------------|-----------------------------------------------------------------------------------|
| Version    | 1.0                                                                               |
| Status     | Draft                                                                             |
| Scope      | Minimum Viable Product (MVP)                                                      |

## 1. Overview
### 1.1 Purpose
This document defines the technical architecture for the Tanzanian Payroll & HR Management System MVP. It translates the business and functional requirements into a concrete technical design to guide software engineers and AI coding agents during implementation.

### 1.2 Relationship to Source Documents
This architecture is strictly derived from the following project source documents:
- `00_SPEC.md`: Defines the technology stack and core architectural principles (SOLID, Clean Architecture).
- `01_BUSINESS_RULES.md`: Dictates the calculation pipeline, concurrency rules, and state machine enforcement.
- `02_FUNCTIONAL_REQUIREMENTS.md`: Defines the API capabilities, RBAC, and background job requirements.
- `03_DATABASE_SPECIFICATION.md`: Dictates data immutability ("Hard Records"), BCMath precision, and the schema.

### 1.3 Technology Stack
As explicitly mandated in `00_SPEC.md`, the system will be built using:
- **Framework:** Laravel 13
- **Language:** PHP 8.5 (with `BCMath` extension for monetary precision)
- **Database:** PostgreSQL 15+ (hosted on Supabase for MVP; self-hostable on any PostgreSQL-compatible server)
- **Testing:** PHPUnit Feature Tests (using Laravel Pint for code styling)

## 2. Architecture Style
### 2.1 Chosen Pattern
The system employs a **Layered Monolithic Architecture** exposing a **REST API**. Internally, it utilizes the **Service Repository Pattern** as mandated by `00_SPEC.md`. 
- **Controllers:** Kept intentionally thin, responsible only for HTTP request parsing, input validation (via Form Requests), and returning API Resources.
- **Service Layer:** Encapsulates all business logic, particularly the complex multi-stage payroll calculation engine (`01_BUSINESS_RULES.md`).
- **Repository Pattern:** Abstracts database access. Controllers and Services interact with Repositories rather than Eloquent models directly to maintain Clean Architecture principles.

### 2.2 Why It Fits
A Layered Monolith is the optimal choice for a payroll system with strict ACID requirements. The payroll engine relies heavily on relational data integrity (e.g., Maker/Checker flows, snapshotting `Hard Records`). A monolith ensures single-database transactional guarantees for financial operations. PostgreSQL's native UUID type, partial unique indexes, `DECIMAL` precision, and Row Level Security (RLS) capabilities are ideal for this compliance-critical system.

### 2.3 Explicit Exclusions
Unless explicitly mandated in future revisions of `00_SPEC.md`, this system **DOES NOT USE**:
- Microservices architecture (undue complexity for MVP)
- Event Sourcing (standard relational state management with strict append-only audit logging is used instead)
- GraphQL (REST API is mandated)

## 3. Application Layer Structure
### 3.1 Directory/Module Structure
The application code will be structured logically by domain rather than purely by Laravel defaults. Key domains map directly to the Domain Grouping Map in `04_DATABASE_ERD.md`:
- `Identity & Access Control` (Auth, Roles, Scopes)
- `Organizational Structure` (Company, Branches, Departments)
- `Employee Lifecycle` (Profiles, Bank Details, Schemes, Documents)
- `Salary Structure & History` (Salary, Grades)
- `Earnings Configuration` (Types, Employee-specific)
- `Deductions Configuration` (Types, Employee-specific)
- `Statutory Rate Configuration` (NSSF, SDL, PAYE brackets)
- `Leave & Attendance` (Public Holidays, Leave, Overtime)
- `Loan Management` (Loans, Installments)
- `Payroll Period Governance` (Open/Closed periods)
- `Payroll Engine (Core)` (Runs, Results, Payslips, Arrears)
- `Bank Export Tracking` (Generated files)
- `System & Audit` (Notifications, Audit Logs)

### 3.2 Key Laravel Concepts

- **Models:** Eloquent models map to `03_DATABASE_SPECIFICATION.md`. `Hard Records` will lack `delete()` capabilities.
- **Services:** Handle core logic (e.g., `PayrollCalculationService`, `BankExportService`).
- **Repositories:** Handle Eloquent queries.
- **Form Requests:** Handle input validation prior to controller execution.
- **API Resources:** Format JSON responses consistently.
- **Jobs:** Handle long-running tasks asynchronously (e.g., `GeneratePayslipsJob`).
- **Events & Listeners:** Decouple side effects (e.g., `PayrollRunApproved` event triggers the `SendPayslipNotifications` listener).
- **Policies:** Enforce RBAC per `02_FUNCTIONAL_REQUIREMENTS.md` §3.2.

### 3.3 Third-Party Package Decisions

To accelerate delivery without compromising the compliance guarantees in `03_DATABASE_SPECIFICATION.md`, the following package decisions are mandated. AI coding agents must follow these exactly — do not substitute, do not implement both a package and a custom equivalent side by side.

| Concern | Decision | Package | Rationale |
|---|---|---|---|
| Authentication | **Use package** | `laravel/sanctum` | Already mandated in `00_SPEC.md` / §7.1. Token + SPA session auth. |
| RBAC (roles) | **Use package, constrained** | `spatie/laravel-permission` | Provides Gate integration, Blade `@can` directives, and route middleware (`role:payroll_officer`) for free. **Constraint: the package's `roles` table REPLACES the custom `user_roles` table defined in `03_DATABASE_SPECIFICATION.md` §5.1 — it does not supplement it.** Only the 9 roles in `02_FUNCTIONAL_REQUIREMENTS.md` §3.1 may ever be seeded. The package's granular `permissions` table and `givePermissionTo()` API must NOT be used — this system is role-based only, not permission-based, per the PRD's fixed 9-role model. |
| Audit Logging | **Do NOT use package** | ~~`spatie/laravel-activitylog`~~ | Rejected. The package's `activity_log` schema (`causer`, `subject`, `properties`, generic `event` string) does not satisfy `03_DATABASE_SPECIFICATION.md` §5.13 (`audit_logs` requires `ip_address`, a controlled `audit_event_type` ENUM, and `ON DELETE RESTRICT` on the causer FK). Build a single custom `AuditObserver` instead (see §8). This is less code than retrofitting the package and removes ambiguity about which table is the system of record. |

> **Migration note:** Because `spatie/laravel-permission` owns the `roles`, `model_has_roles`, and `role_has_permissions` tables, the migration originally specified for `user_roles` in `03_DATABASE_SPECIFICATION.md` §5.1 must be removed and replaced by the package's published migration, seeded with exactly the 9 roles from `02_FUNCTIONAL_REQUIREMENTS.md` §3.1. The `user_role` ENUM in `03_DATABASE_SPECIFICATION.md` §4.3 becomes the authoritative seed list for the package's `roles` table rather than a column type.

## 4. Payroll Calculation Engine
### 4.1 Calculation Pipeline Mapping
The core engine (`PayrollCalculationService`) implements the pipeline defined in `01_BUSINESS_RULES.md` §1:
- **Stage A (Gross Pay):** Calculates prorated basic salary + non-taxable allowances + taxable allowances + overtime.
- **Stage B (Pre-Tax Deductions & Taxable Income):** Calculates Statutory Deductions (NSSF, etc.) on Gross Salary, subtracts from Gross Taxable Salary to get Taxable Income, and applies progressive stacked brackets (`paye_brackets`) for PAYE.
- **Stage C (Net Pool & Post-Tax Deductions):** Calculates Net Pool (Gross - Pre-Tax Deductions - PAYE), processes post-tax deductions (standard and loan installments), and calculates Employer Statutory Contributions (NSSF Employer, WCF, SDL).

### 4.2 Transaction Boundaries
Per `01_BUSINESS_RULES.md` §9.4 and general engine design, the entire payroll calculation for a single run must be wrapped in a database transaction (`DB::transaction()`). If any employee's calculation fails, the entire run calculation rolls back to prevent partial states.

### 4.3 Concurrency Handling
Per `01_BUSINESS_RULES.md` §9.4 (Concurrency Limits), concurrent state transition attempts must be serialized. A pessimistic lock (`lockForUpdate()`) must be applied to the `payroll_runs` record during ANY state transition operation (especially Approve and Calculate actions) to prevent race conditions.

### 4.4 Pre-flight Validation
Per `01_BUSINESS_RULES.md` §9.5, the engine executes a pre-flight check before calculation. Results are stored in `payroll_preflight_results` as either `ERROR` (blocks calculation) or `WARNING` (allows calculation).

### 4.5 Snapshot Generation
When a run transitions to the `preview` state (i.e., at the time of calculation), the engine converts operational data into `Hard Records`. It writes denormalized `payslip_line_items` (per DB Spec Principle 2.1) to guarantee historical immutability, completely decoupling the payslip from any future changes to configuration tables before the Maker reviews it.

## 5. State Machine Implementation
### 5.1 Enforcing States
The state of a payroll run (`draft` → `validated` → `preview` → `approved` → `locked` → `filed`) and its branching terminal states (`locked` → `reversed`, `filed` → `amended`) are enforced via the `status` column on `payroll_runs` and documented in `payroll_run_state_logs`. Transitions are handled by a dedicated `PayrollStateTransitionService` that guards illegal state changes.

### 5.2 Maker/Checker Enforcement
Per `01_BUSINESS_RULES.md` §9.3, separation of duties is enforced at the code level:
1. The transition from `preview` to `approved` (Maker submits for Checker review) captures the `submitted_by_user_id`.
2. The transition from `approved` to `locked` (Checker approval) captures the `approved_by_user_id`.
3. An explicit authorization check (via Laravel Policy) ensures `Auth::id() !== $run->submitted_by_user_id`.

## 6. Background Jobs & Queues
### 6.1 Asynchronous Operations
To ensure the REST API remains responsive, the following operations are dispatched to the queue:
- **Payroll Calculation:** For the MVP, calculation is performed **synchronously** within the web request limit (designed for up to 500 active employees per `02_FUNCTIONAL_REQUIREMENTS.md` Section 9). Background dispatch (`CalculatePayrollRunJob`) is a documented future enhancement for when headcount exceeds 500.
- **Bank File Export Generation:** Dispatched as `GenerateBankExportJob` (FR-BANK-002).
- **Email Notifications:** Dispatched as standard queued notifications.
- **Pre-flight Validations:** Per `02_FUNCTIONAL_REQUIREMENTS.md` Section 9, the system is designed to handle up to 500 active employees within synchronous web request limits. Pre-flight validation must therefore execute synchronously for MVP to guarantee it completes before the state transitions to `validated`.

### 6.2 Queue Configuration
In the MVP, the queue driver defaults to the database (`database` connection) using a `jobs` table, aligning with the single-database PostgreSQL architecture.

### 6.3 Notification Dispatch
Per FR-NOTIF-001–004, notifications (e.g., Maker notified on rejection) use Laravel's Notification facade, pushed to the queue to prevent HTTP blocking.

## 7. Authentication & Authorization

### 7.1 Authentication

The REST API uses **Laravel Sanctum** (`laravel/sanctum`) for token-based authentication (FR-AUTH-001). For MVP, standard API tokens or session-based SPA authentication will be utilized depending on the frontend client.

### 7.2 RBAC Implementation

Role-Based Access Control is implemented using **`spatie/laravel-permission`**, constrained as described in §3.3. The package's `HasRoles` trait is added to the `User` model. The 9 roles defined in `02_FUNCTIONAL_REQUIREMENTS.md` §3.1 are seeded once via a dedicated `RoleSeeder` and must never be created dynamically through the application UI.

Role checks use the package's native API:

```php
$user->hasRole('payroll_officer');   // boolean check
$user->assignRole('finance_manager'); // seeding/admin assignment only
```

Route-level enforcement uses the package's middleware alias, registered as a Laravel 13 `#[Middleware]` attribute on controllers:

```php
#[Middleware('role:payroll_officer')]
class PayrollRunController extends Controller { ... }
```

Blade/API-facing authorization checks may use `$user->hasRole(...)` directly inside Policies — see §7.3. The package's permission-granularity features (`givePermissionTo`, `can()` against ad-hoc permission strings) are explicitly unused; all authorization decisions are role-based, matching the fixed 9-role model in the PRD.

### 7.3 Policy Enforcement

Authorization is enforced via Laravel Policies attached to Form Requests and Controller methods (e.g., `PayrollRunPolicy`). Policies call `$user->hasRole(...)` (via `spatie/laravel-permission`) rather than querying a custom pivot table. Scoped access (e.g., `department_manager` viewing only their department per FR-AUTH-006) is enforced dynamically inside Repositories by applying query scopes (`whereIn('department_id', $scopes)`), independent of the package.

## 8. Audit Logging Implementation

### 8.1 Decision: Custom Observer, Not a Package

Per §3.3, `spatie/laravel-activitylog` is explicitly **not used**. The compliance requirements in `03_DATABASE_SPECIFICATION.md` §5.13 and `01_BUSINESS_RULES.md` §8 (IP address capture, controlled `audit_event_type` ENUM, `ON DELETE RESTRICT` on the causer) are cheaper and safer to implement directly than to retrofit a third-party schema.

### 8.2 Observer Pattern

The system leverages Laravel Eloquent Observers to satisfy BR §8 (Every change logged). A single `AuditObserver` hooks into the `created`, `updated`, `deleted`, and `restored` model events and is registered against every model listed in `03_DATABASE_SPECIFICATION.md` Section 5 that is not itself the `audit_logs` table.

### 8.3 Audit Triggers

When triggered, the observer:
1. Compares `$model->getOriginal()` with `$model->getAttributes()` to build `old_values` / `new_values` JSON payloads (delta only, per DB Spec §10.2).
2. Resolves the acting user from `Auth::id()` and the request IP from `request()->ip()`.
3. Maps the triggering event to the correct `audit_event_type` ENUM value defined in `03_DATABASE_SPECIFICATION.md` §4.3 (e.g., model `updated` → `updated`; a payroll state change is logged separately as `state_transition` by the `PayrollStateTransitionService`, not by this generic observer).
4. Writes a single row to `audit_logs` (FR-AUD-002).

### 8.4 Privilege Restrictions

Per `03_DATABASE_SPECIFICATION.md` §10.3, the application must be designed such that `audit_logs` and `payroll_run_state_logs` are strictly Append-Only. No Eloquent model should expose a mechanism to delete these records, and no third-party package's cleanup/retention commands (e.g., activity log pruning) are installed, since `01_BUSINESS_RULES.md` §10 mandates a 7–10 year retention floor with no automated deletion.

## 9. API Design
### 9.1 REST Conventions
- Use standard HTTP methods: `GET` (read), `POST` (create), `PUT`/`PATCH` (update), `DELETE` (logical soft-delete). Note: Hard Record endpoints must explicitly return `405 Method Not Allowed` on any `DELETE` attempt.
- Pluralized noun endpoints (e.g., `/api/v1/employees`).
- Nested routes for sub-resources where logically strictly bound (e.g., `/api/v1/payroll-runs/{run}/results`).

### 9.2 Response Format Standards
All API responses must be wrapped in Laravel API Resources to guarantee a consistent JSON shape:
```json
{
  "data": { ... },
  "meta": { ... }
}
```

### 9.3 Error Response Shapes
Standardized error responses using Laravel's exception handler:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "nssf_number": ["The NSSF number format is invalid."]
  }
}
```

## 10. File Generation
### 10.1 Export Generation
- **Payslips (PDF):** Generated using a headless PDF library (`spatie/laravel-pdf`) fed by Blade views.
- **Reports (Excel/CSV):** Generated using `Maatwebsite/Laravel-Excel` for performance and memory efficiency.

### 10.2 Bank Export CSV Format
Per `01_BUSINESS_RULES.md` §8 and FR-BANK-002, the bank export output must follow standard CSV conventions. The exact schema of the CSV is generated by the `BankExportService`. Crucially, the generated file is hashed (`file_hash` SHA-256) and the hash is stored in `bank_exports` to prevent manual tampering before upload to the banking portal.

## 11. Infrastructure & Deployment
### 11.1 Hosting Environment
The MVP is designed to run against a PostgreSQL 15+ server. For MVP, the database is hosted on **Supabase** (managed PostgreSQL). Self-hosted deployments may use any PostgreSQL 15+ server.
- **Web Server:** Nginx or Apache.
- **PHP:** PHP-FPM 8.5.
- **PostgreSQL Driver:** `pdo_pgsql` PHP extension required.
- **Cron:** Required for Laravel Scheduler (e.g., `* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`).

### 11.2 Environment Configuration
Environment variables (`.env`) control sensitive configurations (App Key, database DSN, Sanctum settings, Mailer credentials). Required variables: `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT` (default 5432), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

### 11.3 Backup & PITR Requirements
Per `03_DATABASE_SPECIFICATION.md` §14, Point-in-Time Recovery (PITR) must be enabled at the PostgreSQL server level using **WAL archiving** (`wal_level=replica` or higher). For MVP hosted on Supabase, PITR is available as a Supabase platform feature. Self-hosted deployments must configure `pg_basebackup` or use a managed backup service (e.g., Barman, pgBackRest) with an off-site, read-only backup target.

## 12. Security Considerations
### 12.1 Input Validation
All incoming data is validated strictly using Laravel Form Requests. No raw request data is passed to Services or Repositories without prior validation.
### 12.2 CSRF & XSS Protection
- **CSRF:** Handled by Laravel Sanctum for SPA authenticated sessions.
- **XSS:** Handled natively by Laravel API Resources and Blade templates (for PDF generation) which auto-escape output.
### 12.3 Rate Limiting
Laravel's `ThrottleRequests` middleware protects authentication endpoints (`/api/v1/auth/login`, `/api/v1/auth/password-reset`) against brute-force attacks.
### 12.4 Data Encryption
Passwords are hashed using `bcrypt` (Laravel 13 default). Standard TLS/HTTPS must be enforced at the load balancer or web server level for data in transit.
