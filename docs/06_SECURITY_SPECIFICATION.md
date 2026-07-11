# 06_SECURITY_SPECIFICATION.md
# Security Specification Document
## Tanzanian Payroll & HR Management System

---

| Attribute  | Value                                                                             |
|------------|-----------------------------------------------------------------------------------|
| Version    | 1.0                                                                               |
| Status     | Draft                                                                             |
| Scope      | Minimum Viable Product (MVP)                                                      |

## 1. Overview
### 1.1 Purpose and Scope
This document defines the security architecture and compliance safeguards for the Tanzanian Payroll & HR Management System MVP. It translates business requirements for data protection, segregation of duties, and auditability into strict technical controls.

### 1.2 Governing Documents
This specification is derived exclusively from and acts in service of:
- `00_SPEC.md`
- `01_BUSINESS_RULES.md`
- `02_FUNCTIONAL_REQUIREMENTS.md`
- `03_DATABASE_SPECIFICATION.md`
- `05_SYSTEM_ARCHITECTURE.md`

### 1.3 Security Design Goals
1. **Absolute Immutability of Financial Records**: Ensuring historical payroll data cannot be altered after approval.
2. **Strict Segregation of Duties**: Enforcing Maker/Checker workflows at the database level.
3. **Comprehensive Auditability**: Providing a complete, tamper-proof audit trail for regulatory compliance (TRA).

### 1.4 Security Principles

The following principles guide every control in this document:

| Principle | Application in This System |
|---|---|
| **Least Privilege** | Each role is granted only the minimum access required for its function. No role is elevated by default. |
| **Defense in Depth** | Controls exist at the Policy, Repository, and Database layers. No single layer is the sole guard for any critical operation. |
| **Fail Secure** | On any authentication or authorization failure, the system must default to denial, not a degraded but accessible state. |
| **Secure by Default** | All API routes require authentication by default. Public routes must be explicitly and deliberately whitelisted. |
| **Separation of Duties** | The Maker and Checker must always be distinct users, enforced both in application logic and at the database layer. |
| **Principle of Complete Mediation** | Every request to a protected resource must be re-validated for authorization — there is no trusted implicit session state. |
| **Immutable Financial Records** | Approved payroll data is written once and can never be altered. Corrections require formal audit-tracked workflows. |

## 2. Authentication
### 2.1 Session and Token Management
Per `05_SYSTEM_ARCHITECTURE.md` Section 7.1 and `02_FUNCTIONAL_REQUIREMENTS.md` (FR-AUTH-001), the REST API uses **Laravel Sanctum** for token-based authentication or session-based SPA authentication. 

### 2.2 Password Policy
Passwords are securely hashed using `bcrypt` (Laravel 13 default), as defined in `05_SYSTEM_ARCHITECTURE.md` Section 12.4. Passwords must never be logged or transmitted in plain text.

### 2.3 Password Reset Flow and Token Expiry
Per `02_FUNCTIONAL_REQUIREMENTS.md` (FR-AUTH-004), the system provides a secure password reset workflow utilizing the `password_reset_tokens` table. Tokens are temporary and auto-expire to prevent replay attacks.

### 2.4 Account Deactivation Behavior
Accounts are soft-deleted via the `deleted_at` timestamp. Deactivated accounts immediately lose all API access and token validity. They are retained purely for historical audit integrity.

### 2.5 Failed Login Handling
Per standard Laravel authentication safeguards (aligned with FR-AUTH-001–007), rate limiting (e.g., `ThrottleRequests` middleware) must be applied to login endpoints to prevent brute-force attacks.

### 2.6 Session and Token Expiration

To limit the window of exploitation for stolen credentials or tokens, the following timeouts are mandated:

| Policy | Value |
|---|---|
| **Idle Timeout** | 30 minutes of inactivity invalidates the session/token. |
| **Absolute Timeout** | 8 hours maximum regardless of activity. |
| **Logout Behavior** | Immediately revokes the current Sanctum token server-side. All subsequent requests with the revoked token must receive a `401 Unauthorized`. |
| **Token Revocation** | On account deactivation (`deleted_at` set), all associated Sanctum tokens must be immediately purged via `$user->tokens()->delete()`. |

## 3. Authorization (RBAC)

### 3.1 The 9 Roles and Permission Boundaries

Authorization maps strictly to the 9 roles defined in `02_FUNCTIONAL_REQUIREMENTS.md` §3.1 and the `user_role` ENUM in `03_DATABASE_SPECIFICATION.md` §4.3: `system_administrator`, `hr_manager`, `hr_officer`, `payroll_officer`, `finance_manager`, `finance_officer`, `department_manager`, `employee`, `auditor`. Roles are managed and assigned strictly using the `spatie/laravel-permission` package (which replaces the custom `user_roles` pivot table per `05_SYSTEM_ARCHITECTURE.md` §3.3).

### 3.2 Permission Enforcement Layers

Permissions are enforced through a strict defense-in-depth approach:
1. **Policy Layer (Application):** Laravel Policies intercept API requests to ensure the user possesses the correct role before executing a controller action.
2. **Repository Layer (Application):** Query scopes dynamically filter results based on user permissions (e.g., `whereIn('department_id', $scopes)`).
3. **Database Layer (Integrity, not permissions):** CHECK constraints provide a final, role-independent backstop against illegal data states (e.g., the Maker/Checker identity constraint) that would survive even an application-layer bug. This layer enforces data integrity, not authorization — it has no concept of “who is logged in,” only what combinations of stored values are structurally invalid. See §6.1 for the specific caveat on what this constraint does and does not guarantee.

### 3.3 Maker/Checker Segregation Enforcement

Per `01_BUSINESS_RULES.md` §9.3, the Maker (who submits a payroll run) and the Checker (who approves it) must be two distinct identities. The live, authenticated check that prevents a user from approving their own submission (`Auth::id() !== $run->submitted_by_user_id`) is enforced via a Laravel Policy and is the primary internal fraud control. This is distinct from — and not replaced by — the database CHECK constraint described in §6.1.

### 3.4 Department Manager Scope Enforcement

Per `02_FUNCTIONAL_REQUIREMENTS.md` §3.2, a `department_manager` may only access data for their specific department. This is enforced by mapping their identity through the `user_department_scopes` table and applying mandatory repository query scopes.

## 4. Data Protection

### 4.1 Data Classification

All payroll runs, salary histories, and statutory calculations are classified as highly sensitive financial data and are treated as `Hard Records`. Employee PII (TIN, NSSF Number, Bank Account Details) is classified as confidential.

### 4.2 Encryption at Rest and in Transit

- **In Transit**: Standard TLS/HTTPS must be enforced at the web server/load balancer level for all API traffic (`05_SYSTEM_ARCHITECTURE.md` §12.4).
- **At Rest**: For MVP hosted on Supabase, data at rest is protected by Supabase's platform-level encryption. For self-hosted PostgreSQL deployments, Transparent Data Encryption (TDE) via filesystem-level encryption (e.g., dm-crypt/LUKS on Linux, or encrypted EBS volumes on AWS) is strongly recommended. TDE at the PostgreSQL engine level is not in scope for the MVP. **This is an accepted risk for non-Supabase deployments and must be revisited before production deployment outside a managed hosting environment.**

### 4.3 Password Hashing Standard

All passwords are mathematically hashed using `bcrypt` (`05_SYSTEM_ARCHITECTURE.md` §12.4).

### 4.4 Sensitive Field Handling

Fields such as `tin`, `nssf_number`, and bank account numbers (`employee_bank_details`) must be strictly validated during input and scrubbed from general non-privileged API responses. They should only be visible to HR/Payroll roles or the employee themselves.

## 5. Audit & Compliance
### 5.1 What Must Be Logged
Per `01_BUSINESS_RULES.md` §8, every change to an employee's Basic Salary, Tax Bands, Statutory Rates, or Loan Balances must be logged. This is implemented via the `AuditObserver` pattern (`05_SYSTEM_ARCHITECTURE.md` §8.1), capturing user, timestamp, old values, and new values into `audit_logs`.

### 5.2 Audit Log Immutability Enforcement
Per `03_DATABASE_SPECIFICATION.md` §10.3 (Audit Strategy — Structural Protection), the application must enforce Append-Only rules for `audit_logs` and `payroll_run_state_logs`. Eloquent models for these tables must not expose `delete()` functionality, and API endpoints must return `405 Method Not Allowed` on DELETE attempts.

### 5.3 Retention Policy
Per `01_BUSINESS_RULES.md` §10, all audit logs and financial data must be retained for 7–10 years to comply with Tanzanian statutory requirements.

### 5.4 TRA Audit Reproduction Requirements
The architecture guarantees that historical payroll calculations can be exactly reproduced for TRA audits. This is achieved by storing static snapshot data in `payroll_run_results` and `payslip_line_items` instead of relying on dynamic queries to current rates.

### 5.5 Sensitive Data Logging Prohibition

The following data categories must **never** appear in application logs, error logs, queue payloads, or debug output:

- Passwords or password hashes
- Authentication tokens or cookies
- Authorization header values
- Bank account numbers
- TIN numbers
- NSSF numbers
- Any raw PII from employee records

This applies to all logging drivers (`single`, `daily`, `stack`). Laravel's exception handler must be configured to strip sensitive keys before logging request data.

## 6. Payroll-Specific Security
### 6.1 Maker/Checker Database Constraint

To prevent application-level bypasses, `01_BUSINESS_RULES.md` §9.3 and `03_DATABASE_SPECIFICATION.md` mandate a database-level `CHECK` constraint on the `payroll_runs` table: `CHECK (submitted_by_user_id != approved_by_user_id)`.

**Caveat — scope of this constraint:** This constraint guards against duplicate identity *storage* (the same `user_id` being written to both columns on the same row), not duplicate identity *action* at the moment of approval. The constraint also passes when either column is `NULL`, which is the expected and valid state before a run has been submitted or approved. The constraint cannot itself know which user is currently authenticated — that live check (preventing a Maker from approving their own submission) is enforced exclusively in the application Policy layer per §3.3. The two controls are complementary, not redundant: the Policy layer stops the action from happening; the CHECK constraint stops the resulting data from ever being structurally invalid, even if the Policy layer were bypassed by a bug, a direct database write, or a future code change.

### 6.2 Hard Record Protection
Per `03_DATABASE_SPECIFICATION.md` Principles 2.4 and 2.5, financial records transition to `Hard Records` (Immutable) upon period lock. These records must never be `UPDATE`d or `DELETE`d. Corrections require formal `Amended` or `Reversed` workflows.

### 6.3 Snapshot Integrity
As defined in `05_SYSTEM_ARCHITECTURE.md` §4.5, snapshots are generated when the run transitions to the `preview` state, guaranteeing that the Checker reviews the exact, immutable numbers that will be locked into the ledger.

### 6.4 Bank Export File Integrity
Per `01_BUSINESS_RULES.md` §8 and `05_SYSTEM_ARCHITECTURE.md` §10.2, generated Bank Export CSV files are hashed using SHA-256. The hash is stored in `bank_exports` to detect manual tampering before banking portal upload.

### 6.5 Payroll Run Concurrency Protection
Per `01_BUSINESS_RULES.md` §9.4, concurrent modification attempts against a single payroll run are strictly serialized using a pessimistic lock (`lockForUpdate()`) to prevent race conditions during state transitions (Approve/Calculate).

## 7. API Security
### 7.1 Authentication on Every Endpoint
All API endpoints (except explicit public endpoints like login and password reset) must be protected by the Sanctum authentication middleware.

### 7.2 Rate Limiting
Laravel's `ThrottleRequests` middleware is applied to authentication endpoints (FR-AUTH-001) to protect against credential stuffing and brute-force attacks.

### 7.3 Input Validation
All incoming data is sanitized and validated strictly via Laravel Form Requests prior to reaching the controller layer.

### 7.4 CSRF and XSS Protection
- CSRF protection is handled natively by Laravel Sanctum for session-based authenticated clients.
- XSS protection is guaranteed by Laravel API Resources returning structured JSON and Blade templates automatically escaping output for PDF generation.

### 7.5 Error Response Security
API error responses must not leak sensitive database structures, stack traces, or field-level disclosure on authentication failures (e.g., returning generic "Invalid credentials" rather than "User not found").

### 7.6 Security Response Headers

All API and web responses must include the following HTTP security headers. These may be applied via Laravel middleware or web server (Nginx/Apache) configuration:

| Header | Required Value |
|---|---|
| `Content-Security-Policy` | `default-src 'self'` (adjusted per PDF/Blade view requirements) |
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |

## 8. Infrastructure Security
### 8.1 TLS/HTTPS Requirement
HTTPS must be enforced via TLS at the web server/load balancer level for all data in transit.

### 8.2 Environment Variable Management
Sensitive configuration data (App Key, database keys, mailer credentials) must be managed exclusively via the `.env` file and excluded from version control.

### 8.3 PostgreSQL Access Controls
The PostgreSQL database user used by the application must be granted only the minimum necessary privileges (`SELECT`, `INSERT`, `UPDATE` on operational tables; `INSERT`-only on `audit_logs` and `payroll_run_state_logs`). Direct shell or GUI access to the production database must require multi-factor authentication and must be logged. For Supabase-hosted deployments, connection pooling (PgBouncer) credentials must be stored exclusively in `.env` and never committed to source control.

### 8.4 Backup Security
Per `03_DATABASE_SPECIFICATION.md` §14, PostgreSQL WAL-based backups must be shipped to off-site, read-only storage to prevent malicious tampering or ransomware attacks, satisfying Point-in-Time Recovery (PITR) requirements. For Supabase-hosted deployments, platform PITR satisfies this requirement. Self-hosted deployments must configure `pg_basebackup` or equivalent.

### 8.5 File Upload Security

The MVP does not include a generic file upload feature. When document upload (employee contracts, loan documents, leave attachments) is introduced in a future release, the following controls are mandatory:

- Validate MIME type server-side (not from the client-supplied `Content-Type`).
- Validate file extension against an explicit allowlist.
- Enforce maximum file size limits.
- Generate cryptographically random filenames on storage to prevent enumeration.
- Never execute uploaded files under any circumstance.
- Store all uploaded files outside the public web root (i.e., in `storage/app/private/`).

### 8.6 Secrets Rotation

Production secrets (App Key, Sanctum secrets, mailer credentials) must be rotatable without application code changes. Configuration must be externalized such that a secret rotation requires only a `.env` update and a process restart — no code commit, deployment, or migration.

## 9. Out of Scope (MVP)
The following advanced security features are explicitly NOT required for the MVP, as they are not defined in the source documents:
- Multi-Factor Authentication (MFA)
- Single Sign-On (SSO / SAML / OAuth)
- Database-level encryption at rest (Transparent Data Encryption / TDE) — see §4.2 for the accepted-risk rationale
- Formal SOC2 / ISO 27001 Certification Tooling
- Routine automated Penetration Testing pipelines

---

## 10. Security Testing Requirements

Every release must verify the following controls through automated feature tests (PHPUnit) or documented manual verification:

| Control Area | What to Verify |
|---|---|
| **Authentication** | Unauthenticated requests receive `401`. Tokens expire as per §2.6. |
| **Authorization** | Each role can only access its permitted endpoints. Cross-role access returns `403`. |
| **Policies** | Maker cannot approve their own submission. |
| **Audit Logs** | Every create/update/delete writes an immutable row to `audit_logs`. |
| **Maker/Checker** | `submitted_by_user_id = approved_by_user_id` is rejected at the DB level. |
| **Payroll Locking** | No mutation is possible on a `locked` or `filed` run. |
| **Input Validation** | Invalid payloads receive `422`. SQL/XSS payloads are rejected. |
| **Rate Limiting** | Login endpoint throttles after the configured attempt limit. |
| **Snapshot Integrity** | `payslip_line_items` written at `preview` match the locked result values. |

---

## 11. Compliance Mapping

Every security control in this document has a documented origin. The table below traces each control category to its governing source:

| Security Control | Source Document | Reference |
|---|---|---|
| Maker/Checker separation | `01_BUSINESS_RULES.md` | §9.3 |
| Audit trail requirements | `01_BUSINESS_RULES.md` | §8 |
| Audit retention (7–10 years) | `01_BUSINESS_RULES.md` | §10 |
| Password policy | `05_SYSTEM_ARCHITECTURE.md` | §12.4 |
| Snapshot immutability | `05_SYSTEM_ARCHITECTURE.md` | §4.5 |
| RBAC (9 roles) | `02_FUNCTIONAL_REQUIREMENTS.md` | §3.1 |
| Password reset flow | `02_FUNCTIONAL_REQUIREMENTS.md` | FR-AUTH-004 |
| Hard Record protection | `03_DATABASE_SPECIFICATION.md` | Principles 2.4, 2.5 |
| Append-only audit logs | `03_DATABASE_SPECIFICATION.md` | §10.3 |
| PostgreSQL backup & PITR | `03_DATABASE_SPECIFICATION.md` | §14 |
| Concurrency locking | `01_BUSINESS_RULES.md` | §9.4 |
| Bank export SHA-256 | `01_BUSINESS_RULES.md` | §8 |
| TLS/HTTPS | `05_SYSTEM_ARCHITECTURE.md` | §12.4 |

---

## Appendix A: Role Permission Matrix

The following matrix summarizes which roles have access to which system modules. `R` = Read, `W` = Write/Manage, `—` = No Access.

| Module | Sys Admin | HR Manager | HR Officer | Payroll Officer | Finance Manager | Finance Officer | Dept Manager | Employee | Auditor |
|---|---|---|---|---|---|---|---|---|---|
| User Management | W | — | — | — | — | — | — | — | R |
| Company / Branch / Dept | W | R | R | R | R | R | R | — | R |
| Employee Profiles | W | W | W | R | R | R | R (own dept) | R (own) | R |
| Salary Structures | W | W | — | R | W | R | — | — | R |
| Earnings & Deductions Config | W | W | W | R | W | R | — | — | R |
| Statutory Configuration | W | — | — | R | W | R | — | — | R |
| Leave Management | W | W | W | — | — | — | R (own dept) | W (own) | R |
| Loan Management | W | W | W | R | R | R | — | R (own) | R |
| Payroll Run (Maker) | W | — | — | W | — | — | — | — | R |
| Payroll Run (Checker) | W | — | — | — | W | — | — | — | R |
| Payslips | W | R | R | R | R | R | R (own dept) | R (own) | R |
| Bank Export | W | — | — | R | W | R | — | — | R |
| Audit Logs | R | — | — | — | — | — | — | — | R |
| System Settings | W | — | — | — | — | — | — | — | R |

> **Note:** This matrix represents intent derived from `02_FUNCTIONAL_REQUIREMENTS.md` §3.1–3.2. Authoritative enforcement is in the Laravel Policy layer. This table must be updated whenever the role model changes.

---

## Appendix B: Threat Model

The following table maps known threat vectors to their corresponding controls in this system:

| Threat | Attack Vector | Control | Layer |
|---|---|---|---|
| SQL Injection | Malicious input in API parameters | Laravel Form Requests (parameterized queries via Eloquent) | Application |
| XSS | Malicious scripts in stored data | Blade auto-escaping; API Resources return structured JSON | Application |
| CSRF | Forged cross-origin state-changing requests | Laravel Sanctum CSRF cookie for SPA sessions | Application |
| Token Replay | Stolen/expired token reuse | Expiring tokens per §2.6; immediate revocation on logout | Application |
| Privilege Escalation | User accessing above their role | Laravel Policies + `spatie/laravel-permission` checks | Application |
| Insider Payroll Tampering | Payroll officer modifying results after approval | Hard Records; immutable `payslip_line_items`; no UPDATE on locked runs | DB + Application |
| Double Approval (Self-Approval) | Maker approving their own submission | Policy check + DB `CHECK (submitted_by != approved_by)` | Application + DB |
| Race Condition | Two users simultaneously transitioning a payroll run | Pessimistic lock `lockForUpdate()` on `payroll_runs` | Application |
| Bank File Tampering | Manual modification of CSV before bank upload | SHA-256 hash stored in `bank_exports` at generation time | Application |
| Audit Log Deletion | Covering tracks by deleting audit records | Append-Only Eloquent model; no `DELETE` endpoint | Application + DB |
| Brute Force Login | Credential stuffing on `/api/v1/auth/login` | `ThrottleRequests` middleware rate limiting | Application |
| Sensitive Data Leakage via Logs | PII/tokens appearing in log files | Logging prohibition policy per §5.5 | Application |
