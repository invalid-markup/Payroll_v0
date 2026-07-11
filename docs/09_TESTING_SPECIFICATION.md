# 09_TESTING_SPECIFICATION.md
# Testing Specification Document
## Tanzanian Payroll & HR Management System

---

## 1. Overview
This Testing Specification defines the comprehensive testing architecture for the Tanzanian Payroll & HR Management System. Derived strictly from the MVP source documents (00 to 08), this document acts as the single source of truth for QA and Engineering teams to guarantee system stability, data integrity, and strict adherence to Tanzanian payroll regulations.

## 2. Testing Philosophy
Testing must be proactive, comprehensive, and automated. No code is merged without corresponding tests that prove its functionality against business rules. 

## 3. Testing Principles
- **Traceability:** Every test must map to a Functional Requirement or Business Rule.
- **Determinism:** Tests must be reliable, independent, and repeatable.
- **Immutability First:** Testing must aggressively protect Hard Records and snapshots from mutation.

## 4. Testing Layers
1. **Unit Testing:** Isolated testing of individual classes (calculators, formatters).
2. **Feature/Integration Testing:** End-to-end API workflows with DB interactions.
3. **Database Testing:** Validating constraints, migrations, and unique indexes.
4. **Security Testing:** Validating RBAC, Maker/Checker rules, and token access.

## 5. Unit Testing
- **Scope:** Services, Action classes, Value Objects, Custom Rules.
- **Approach:** Isolate logic using mocks for external dependencies.
- **Laravel Implementation:** `tests/Unit` using PHPUnit.

## 6. Feature Testing
- **Scope:** Complete API request/response cycles.
- **Approach:** Use Laravel's HTTP testing capabilities acting as authenticated users.
- **Laravel Implementation:** `tests/Feature` using RefreshDatabase trait.

## 7. API Testing
- **Purpose:** Ensure the endpoints listed in `07_API_SPECIFICATION.md` respect payloads and status codes.
- **Expected Behavior:** 200/201 for success, 422 for validation errors, 401/403 for auth errors.

## 8. Database Testing
- **Scope:** `03_DATABASE_SPECIFICATION.md` constraints and table structures.
- **Approach:** Attempt invalid inserts (nulls on required, foreign key violations) to ensure the DB rejects them.
- **Specific Test Cases:**
  - **CHECK constraint with NULLs:** Verify that constraints evaluated on optional fields (where NULL is allowed) do not throw exceptions when a NULL is passed, correctly adhering to the rules documented in `06_SECURITY_SPECIFICATION.md` §6.1.
  - Verify unique indexes on multi-column combinations correctly reject duplicates.

## 9. Business Rule Testing
- **Purpose:** Validate constraints from `01_BUSINESS_RULES.md`.
- **Scope:** Enumerated testing of critical payroll algorithms and thresholds.
- **Specific Test Cases:**
  - **PAYE Bracket Boundary Rule:** Assert that an income of exactly 270,000 falls in the 0% bracket (tax = 0), and an income of exactly 270,001 is taxed under the 8% bracket as (270,001 - 270,000) × 8% = 0.08 TZS. This specifically guards against an off-by-one implementation using >= 270,001 as the bracket minimum instead of > 270,000, which `01_BUSINESS_RULES.md` §2 identifies as a known failure mode.
  - **Residual Absorption Rounding Rule:** Verify that calculations with extensive decimals are rounded precisely at the final step, and the residual difference is accurately logged in the Rounding Adjustment column to balance the ledger.
  - **Arrears 7-Step Algorithm:** Test the complete retroactive arrears processing to ensure historical periods are recalculated and differences aggregated correctly.
  - **Final Installment Loan Closure Rule:** Assert that a loan automatically suspends and closes when the outstanding balance hits exactly zero or the final deduction covers the remainder.

## 10. Validation Testing
- **Purpose:** Ensure `08_VALIDATION_SPECIFICATION.md` rules are enforced.
- **Scope:** Laravel Form Requests and Service-level validations.

## 11. Authentication Testing
- **Scope:** Login, Logout, Password Reset.
- **Assertions:** Valid credentials yield a token; invalid yield 401.

## 12. Authorization (RBAC) Testing
- **Scope:** Spatie Roles & Permissions (`06_SECURITY_SPECIFICATION.md`).
- **Assertions:**
  - Standard RBAC: HR Manager cannot approve payroll runs; only Finance Manager roles can.
  - **Maker/Checker Constraint:** Finance Manager A submits a run → Finance Manager A cannot approve their own submission → Finance Manager B must approve it. This self-approval block is the most critical RBAC test case in the system.

## 13. Payroll Engine Testing
- **Scope:** Module 4.12. Validating State Transitions and Constraints.
- **Approach:** Drive the engine from Draft -> Filed, asserting state at each step.
- **Specific Test Cases:**
  - **Single Open Period Constraint:** Attempt to open a new period (e.g., August) while the current period (e.g., July) is not Locked. Expect a firm rejection (BR §9.2).
  - **Insufficient Funds at Preview Block:** Seed an employee with standard deductions exceeding their net pool. Run calculation and attempt to Submit. Expect failure (FR-PROC-007).

## 14. Payroll Calculation Testing
- **Scope:** Stage A (Gross), Stage B (Tax/Statutory), Stage C (Net).
- **Assertions:** Exact precision matches expected outputs based on statutory rules.

## 15. Payroll Workflow Testing
- **Scope:** End-to-end processing of a period, including complex flows.
- **Specific Test Cases:**
  - **Amended Return Workflow:** Test generating an Amended run against an already Filed period (FR-PROC-014), proving the linkage and correction behavior.
  - **Supplementary Runs:** Test running a secondary off-cycle payroll for a subset of employees within a Locked period (FR-PROC-013).

## 16. State Machine Testing
- **Scope:** Draft -> Validated -> Preview -> Approved -> Locked -> Filed.
- **Failure Condition:** Transitioning Draft -> Approved must fail.

## 17. Effective Dating Testing
- **Scope:** Resolving rates based on `effective_from` <= period_end_date.
- **Expected Behavior:** Historical calculations use historical rates.

## 18. Snapshot Testing
- **Scope:** `payroll_run_results` and `payslip_line_items` generation on Lock.
- **Assertions:** Changing a global rate post-lock does not affect the denormalized data saved in these tables.

## 19. Hard Record Testing
- **Scope:** Payslips, Audit Logs, Locked Runs.
- **Assertions:** Any `UPDATE` or `DELETE` attempt throws an Exception.

## 20. Audit Trail Testing
- **Scope:** Validating that every mutation writes an audit record.
- **Assertions:** Maker ID and Checker ID are permanently logged.

## 21. Security Testing
- **Scope:** Validating endpoint protection, preventing IDOR.

## 22. Concurrency Testing
- **Scope:** Preventing race conditions during state transitions and calculations (BR §9.4).
- **Approach:** 
  - Simulated parallel requests expecting a 409 Conflict.
  - Verify pessimistic locking (`lockForUpdate()`) behaves correctly as per `05_SYSTEM_ARCHITECTURE.md` §4.3.
  - Specific Scenarios: Two users calculating simultaneously, one user calculating while another approves, two users approving simultaneously.

## 23. Performance Testing
- **Scope:** Ensure payroll calculations complete within acceptable limits.

## 24. Integration Testing
- **Scope:** Interactions across modules (Leave feeding into Payroll).

## 25. Notification Testing
- **Scope:** Validating email dispatch on Lock/Submit/Reject.
- **Laravel Implementation:** `Notification::fake()`.

## 26. Report Testing
- **Scope:** Validating Payroll Register, PAYE, NSSF outputs.

## 27. Export Testing
- **Scope:** Bank Export CSV and Payslip PDF.

## 28. Edge Case Testing
- **Scope:** Mid-month hires, terminations, zero-net pay.

## 29. Error Handling Testing
- **Scope:** Ensuring unhandled exceptions return standard JSON format.

## 30. Regression Testing
- **Scope:** Automated suite executed on every PR.

## 31. User Acceptance Testing
- **Scope:** Manual UAT validation against requirements.

## 32. Laravel Testing Strategy
- Use PHPUnit, Faker for seeders, RefreshDatabase.

## 33. Test Environment
- Dedicated PostgreSQL test database (separate schema or `payeasy_test` database). Use Laravel's `RefreshDatabase` trait. Configure `phpunit.xml` with `DB_DATABASE=payeasy_test` env override. A separate test DB ensures production data is never affected by test runs.

## 34. Test Data Strategy
- **Scope:** Factories with realistic Tanzanian data (e.g., TZS currencies, 9-digit TINs).

## 35. Code Coverage Requirements
- **Tier 1 (100% Coverage):** Payroll Engine, Statutory Calculations, Audit Trail, Hard Record Protection.
- **Tier 2 (80%+ Coverage):** Standard CRUD operations, Reports, UI APIs.
- **Exclusions:** Pure DTOs, migrations, and empty interface implementations are exempt from strict line coverage metrics.

## 36. CI/CD Testing Strategy
- Github Actions executing the entire suite on PRs.

## 37. Definition of Done
- Feature complete, 100% tests passing, zero linting errors.

## 38. Traceability Matrix
- Ensures 1:1 mapping between tests and FRs.
