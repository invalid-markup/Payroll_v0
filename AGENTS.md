# AGENT.md — PayEasy Payroll System Rebuild

> **Read this file completely before writing a single line of code.**
> This file is the source of authority for how you work on this project.
> Every decision, every file you touch, every pattern you choose must
> be traceable to one of the spec documents listed here.
> If something is not in the specs — stop and ask. Do not invent it.

---

## 0. What This Project Is

A modern SaaS rebuild of **PayEasy+HR**, a Tanzanian payroll desktop application
(MS Access, single-tenant). The rebuild targets a **multi-tenant web platform**
serving Tanzanian employers and their payroll staff.

**Scope: Payroll module only.**
Attendance (biometric clocks) and the full HRM suite (Recruitment, Grievances,
Disciplinary workflows, Onboarding) are **explicitly out of scope** for this phase.
Do not build, scaffold, seed, or reference them.

---

## 1. Your Document Map

Every aspect of this system is fully specified. Before you work on any area,
open and read the corresponding document first. Do not rely on your training
data or assumptions about "how payroll systems usually work."

| # | File | When to read it |
|---|------|-----------------|
| `docs/00_SPEC.md` | Master specification & project overview | **Always — read first on every session** |
| `docs/01_BUSINESS_RULES.md` | Payroll calculation engine, statutory rules, constraints | Before any calculation, formula, or business-logic code |
| `docs/02_FUNCTIONAL_REQUIREMENTS.md` | Feature list, workflows, module boundaries | Before scaffolding any new feature or module |
| `docs/03_DATABASE_SPECIFICATION.md` | Full table definitions, column types, constraints, comments | Before touching any migration, model, or query |
| `docs/04_DATABASE_ERD.md` | Entity-relationship diagram and relationship narrative | Before writing any Eloquent relationship or join query |
| `docs/05_SYSTEM_ARCHITECTURE.md` | Stack, folder structure, service layer, patterns | Before creating any new class, service, or directory |
| `docs/06_SECURITY_SPECIFICATION.md` | Auth, RLS, roles, permissions, audit trail | Before writing any auth, middleware, policy, or user-facing gate |
| `docs/07_API_SPECIFICATION.md` | Every endpoint: method, URL, payload, response, errors | Before writing any controller, route, or API response |
| `docs/08_VALIDATION_SPECIFICATION.md` | All field-level validation rules, per endpoint | Before writing any FormRequest, validator, or client-side rule |
| `docs/09_TESTING_SPECIFICATION.md` | Test strategy, required test cases, coverage targets | Before writing any test or considering a feature "done" |
| `docs/10_UI_UX_SPECIFICATION.md` | Screen layouts, component patterns, interaction flows | Before building any view, component, or page |

---

## 2. Hard Rules — Never Violate These

These rules exist because the specs were derived from a real statutory system
with legal implications. Breaking them produces incorrect payroll or tax output.

### 2.1 Calculation Engine
- **Never rewrite the payroll formula.** It is defined exactly in `docs/01_BUSINESS_RULES.md`.
  Reproduce it verbatim. If it seems wrong to you, flag it — do not silently "fix" it.
- **All money columns use `NUMERIC` / `decimal`, never `float` or `double`.**
  Floating-point rounding errors in PAYE calculations are a compliance failure.
- **PAYE, SDL, WCF, NSSF/PPF/pension deductions follow Tanzanian law as described
  in the business rules doc.** Do not substitute logic from another country's
  payroll conventions even if they look similar.

### 2.2 Database
- **Do not add, remove, rename, or retype any column without a migration.**
  Every schema change must be a versioned migration file.
- **Do not use `->unsignedBigInteger()` for foreign keys.** All PKs and FKs are
  `UUID`. See `docs/03_DATABASE_SPECIFICATION.md` for the exact column types.
- **Do not soft-delete employees who have left.** Terminated employees must have
  their `employment_status_id` updated to a "Dismissed" status. Hard deletion
  breaks bi-annual statutory reports. This is a documented business rule.
- **Respect the one-active-loan-per-employee constraint.** It is enforced at the
  database level (partial unique index) and must also be enforced at the service
  layer. Never bypass it.
- **Posted payroll months are immutable.** The database trigger enforces this,
  but your service and controller code must also check `payroll_months.status`
  before any write operation and return a clear error if the month is `posted`.

### 2.3 Multi-Tenancy
- **Every query must be scoped to the current company.** RLS policies exist at
  the database level, but every Eloquent query must also explicitly scope to
  `company_id`. Do not rely on RLS alone as the only isolation layer.
- **Set `app.current_company_id` and `app.current_tenant_id` on every database
  connection before executing queries.** See `docs/06_SECURITY_SPECIFICATION.md`
  for the exact mechanism.
- **Never expose data from one company to a user of another.** No shared caches,
  no cross-company joins, no IDs that leak between tenants.

### 2.4 Security
- **Never return password hashes, internal IDs of other tenants, or raw audit
  log data in API responses** unless the endpoint explicitly requires it and
  the authenticated user has Sysop-level access.
- **All permission checks go through the Rights Matrix** defined in
  `docs/06_SECURITY_SPECIFICATION.md`. Do not hard-code role names in controller
  logic — check feature keys against `feature_rights`.
- **Sysop is not a regular admin.** It has unique capabilities (Rollback, Purge,
  Audit Trail, User Management, Loan Adjustments) that no other role can have,
  even if configured with "full access." Enforce this explicitly.

### 2.5 API Contracts
- **Do not add, remove, or rename fields in API responses** without a
  corresponding update to `docs/07_API_SPECIFICATION.md`.
- **All API errors must return the exact structure defined in the spec.**
  Do not return framework-default error shapes.
- **Validation rules in `docs/08_VALIDATION_SPECIFICATION.md` are exhaustive.**
  Do not add "helpful" extra validation that isn't in the spec — it may
  conflict with legitimate business inputs (e.g., a factor of 0 is a valid
  payroll input for an employee on unpaid leave).

---

## 3. Development Workflow — Stage by Stage

Work in this order. Do not skip ahead. Each stage has a gating doc.

```
Stage 1 — Database & Migrations
  Gate: docs/03_DATABASE_SPECIFICATION.md + docs/04_DATABASE_ERD.md
  Deliverable: All migration files, Models with relationships and casts

Stage 2 — Core Business Logic (Calculation Engine)
  Gate: docs/01_BUSINESS_RULES.md
  Deliverable: PayrollCalculationService, tested in isolation with known inputs/outputs

Stage 3 — Authentication & Tenant Scoping
  Gate: docs/06_SECURITY_SPECIFICATION.md
  Deliverable: Login, session, company switching, RLS session setup, Rights middleware

Stage 4 — API Endpoints (module by module)
  Gate: docs/07_API_SPECIFICATION.md + docs/08_VALIDATION_SPECIFICATION.md
  Deliverable: Controllers, FormRequests, Resources, Routes — one module at a time

Stage 5 — UI / Frontend
  Gate: docs/10_UI_UX_SPECIFICATION.md
  Deliverable: Pages and components per the screen specs

Stage 6 — Reporting
  Gate: docs/02_FUNCTIONAL_REQUIREMENTS.md (Reports section)
  Deliverable: All statutory reports (PAYE, SDL, WCF, NSSF/PPF, payslips, bank advice)

Stage 7 — Testing
  Gate: docs/09_TESTING_SPECIFICATION.md
  Deliverable: Unit, feature, and integration tests per the test spec
```

When you complete a stage, say so explicitly and state which stage you are
moving to next. Do not silently start the next stage.

---

## 4. Module Reference

These are the named modules in this system. Use these exact names in file names,
class names, route prefixes, and API paths. Do not rename or merge them.

| Module | Scope |
|--------|-------|
| `company` | Company configuration, branches, settings |
| `employee` | Employee master data and all sub-entities |
| `payroll` | Monthly payroll cycle (entries, calculations, post) |
| `loan` | Employee loans and adjustments |
| `leave` | Leave balances and adjustments |
| `pension` | Pension scheme configuration and receipts |
| `statutory` | PAYE tables, SDL, WCF, NSSF/PPF configuration |
| `reports` | All statutory and operational reports |
| `timesheets` | Time sheet entry and project tracking |
| `users` | App users, access levels, rights matrix |
| `audit` | Event log, audit trail, authorization |
| `config` | Earnings/deductions types, grades/scales, currencies, banks |

---

## 5. Coding Conventions

> The stack and all conventions are defined in `docs/05_SYSTEM_ARCHITECTURE.md`.
> What follows is a quick-reference summary only. The architecture doc governs.

- **Read `docs/05_SYSTEM_ARCHITECTURE.md` before making any structural decision.**
- One Service class per module. Controllers call Services; they do not contain
  business logic directly.
- All database access goes through Eloquent Models. Raw queries are allowed only
  for complex reporting queries — and must be documented with a comment explaining
  why Eloquent was insufficient.
- Every Model must define `$fillable`, `$casts` (money fields as `decimal:2`,
  UUIDs as `string`, booleans as `boolean`), and all relationships.
- Route model binding is required for all resource endpoints.
- FormRequest classes are required for all write endpoints. See
  `docs/08_VALIDATION_SPECIFICATION.md` for rules per endpoint.
- API responses use dedicated Resource / ResourceCollection classes.
  No `->toArray()` returns directly from controllers.

---

## 6. What To Do When You Are Unsure

Follow this decision tree **every time** you hit ambiguity:

```
1. Is the answer in docs/00_SPEC.md?          → Follow it.
2. Is the answer in the relevant stage doc?    → Follow it.
3. Is it in another doc file?                  → Follow it and note which doc.
4. None of the docs cover it?
   → STOP.
   → Write a comment: // GAP: [describe what is missing]
   → Output a question to the developer describing the gap.
   → Do NOT guess. Do NOT invent a "reasonable default."
   → Wait for clarification before proceeding.
```

**The docs are the spec. The spec is the system.**
If you build something that isn't in the spec, you are building the wrong system.

---

## 7. Known Open Questions (Do Not Resolve Without Confirmation)

These gaps exist in the source material and are documented intentionally.
Do not resolve them by guessing — leave placeholder comments and move on.

| # | Gap | Where it appears | Comment placeholder to use |
|---|-----|-----------------|---------------------------|
| 1 | PAYE band `offset` column — is it entered manually or derived from band boundaries? | `docs/01_BUSINESS_RULES.md`, `docs/03_DATABASE_SPECIFICATION.md` | `// GAP-001: PAYE band offset — confirm calculation method with TRA table` |
| 2 | "Loan Taxable" in the taxable-income formula — exact definition unknown | `docs/01_BUSINESS_RULES.md` | `// GAP-002: loan_taxable_amount — pending tax SME confirmation` |
| 3 | SDL statement TRA form code — manual shows ITX215.01.E (same as PAYE), likely ITX219.01.E | `docs/02_FUNCTIONAL_REQUIREMENTS.md` (Reports) | `// GAP-003: SDL form code — confirm correct TRA reference before printing` |

---

## 8. Out of Scope — Do Not Build

Even if a feature seems obviously useful or is tangentially mentioned in the
source manual, do not build it if it is not in the specs. These are explicitly
out of scope for this phase:

- Biometric attendance integration (finger-print clocks, shifts, teams)
- Recruitment module (job analysis, job descriptions, applicant tracking)
- Onboarding workflows
- Full disciplinary case management (misconduct levels, consequences, hearings)
- Grievance management
- Employee self-service portal
- Mobile application
- QuickBooks / accounting software integration (QB account numbers are stored
  as reference data only — no sync logic in this phase)
- Email sending of payslips (the data model supports it; the sending mechanism
  is out of scope for this phase)

If a user or developer asks you to build any of the above: acknowledge the
request, note it as a future-phase item, and decline to implement it now.

---

## 9. Statutory Compliance Notes

These are non-negotiable legal constraints, not design preferences.

- **PAYE** is calculated monthly using the banded TRA table. Annual income is
  NOT used as the basis — monthly taxable income is.
- **SDL** is calculated as a percentage of gross payroll. The percentage is
  configurable per company. Some employees may be individually exempt.
- **WCF** is based on gross earnings of permanent employees only. Casual/daily
  workers may have different treatment — see `docs/01_BUSINESS_RULES.md`.
- **NSSF/PPF/ZSSF** contributions differ in their calculation base: ZSSF uses
  basic salary; all others use gross salary by default. This distinction is
  critical and must be faithfully reproduced.
- **HESLB student loan** deductions are a separate statutory obligation, tracked
  per-employee and reported separately.
- **Pension contributions are not taxable income** (Income Tax Act 2006 s.61).
  Insurance-type contributions may be taxable. See `docs/01_BUSINESS_RULES.md`.
- **Do not hardcode tax rates, percentages, or thresholds.** All statutory
  figures are stored in configuration tables and must be read from the database.

---

## 10. Session Startup Checklist

Run through this at the start of every coding session:

- [ ] Read `docs/00_SPEC.md` (the overview — 2 minutes)
- [ ] Identify which Stage you are in (Section 3 above)
- [ ] Open the gate document(s) for that stage
- [ ] Check the open questions list (Section 7) — are any relevant to today's work?
- [ ] Confirm the module you are working on (Section 4)
- [ ] Confirm you are not about to build anything in the out-of-scope list (Section 8)

Then and only then: start writing code.

---

