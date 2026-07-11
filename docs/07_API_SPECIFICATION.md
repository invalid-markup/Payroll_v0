# 07_API_SPECIFICATION.md
# API Specification Document
## Tanzanian Payroll & HR Management System

---

| Attribute       | Value                                                                 |
|-----------------|-----------------------------------------------------------------------|
| Version         | 1.0                                                                   |
| Status          | Draft                                                                 |
| Scope           | Minimum Viable Product (MVP)                                          |
| Relates To      | `02_FUNCTIONAL_REQUIREMENTS.md`, `06_SECURITY_SPECIFICATION.md`       |

---

## Table of Contents

1. Overview
2. API Standards & Conventions
3. Authentication & Authorization (Module 4.01)
4. Company Management (Module 4.02)
5. Employee Management (Module 4.03)
6. Salary Structure Management (Module 4.04)
7. Earnings Management (Module 4.05)
8. Deductions Management (Module 4.06)
9. Statutory Compliance Configuration (Module 4.07)
10. Leave Management (Module 4.08)
11. Attendance & Overtime (Module 4.09)
12. Loan Management (Module 4.10)
13. Payroll Period Management (Module 4.11)
14. Payroll Processing Engine (Module 4.12)
15. Payslip Management (Module 4.13)
16. Bank Export (Module 4.14)
17. Reports (Module 4.15)
18. Notifications (Module 4.16)
19. Audit Logs (Module 4.17)
20. System Settings (Module 4.18)

---

## 1. Overview

This document defines the RESTful API contract for the Tanzanian Payroll & HR Management System MVP. It translates the functional requirements into specific endpoints, request payloads, and response structures. All endpoints are designed to interact seamlessly with the Laravel 13 backend, enforcing the business rules and security specifications defined in earlier documents.

### 1.1 Base URL
All API endpoints are relative to the following base URL:
`https://{domain}/api/v1`

---

## 2. API Standards & Conventions

### 2.1 Request & Response Format
- All requests must include the `Accept: application/json` header.
- For `POST`, `PUT`, and `PATCH` requests, the `Content-Type: application/json` header is required.
- All responses will be in JSON format, structured using Laravel API Resources.

**Standard Success Response:**
```json
{
  "data": {
    // Resource object(s)
  },
  "meta": {
    // Pagination or additional meta data (if applicable)
  }
}
```

**Standard Error Response:**
```json
{
  "message": "Human-readable error message.",
  "errors": {
    "field_name": ["Validation error detail."]
  }
}
```

### 2.2 Authentication
All endpoints (unless explicitly marked public) require authentication via Laravel Sanctum.
- Clients must pass the token in the Authorization header: `Authorization: Bearer {token}`
- Alternatively, for SPA clients, session-based authentication using Sanctum's CSRF cookie is supported.

### 2.3 Pagination
List endpoints that return multiple records must implement pagination. The response `meta` object will contain pagination details (current_page, last_page, per_page, total).

### 2.4 HTTP Status Codes
- `200 OK`: Request succeeded.
- `201 Created`: Resource successfully created.
- `204 No Content`: Resource successfully deleted or action completed without returning data.
- `400 Bad Request`: Invalid request parameters or business logic violation.
- `401 Unauthorized`: Missing or invalid authentication token.
- `403 Forbidden`: Authenticated user lacks the required role/permissions.
- `404 Not Found`: Requested resource does not exist.
- `422 Unprocessable Entity`: Validation failed on the input data.
- `500 Internal Server Error`: Server encountered an unexpected condition.

---

## 3. Authentication & Authorization (Module 4.01)

### 3.1 User Login (Public)
Authenticates a user and returns a token.

- **Endpoint:** `POST /auth/login`
- **FR Reference:** FR-AUTH-001
- **Request Body:**
```json
{
  "email": "user@example.com",
  "password": "securepassword123"
}
```
- **Success Response (200 OK):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "roles": ["hr_manager"]
    },
    "token": "1|abc123def456ghi789jkl"
  }
}
```

### 3.2 User Logout
Terminates the current session.

- **Endpoint:** `POST /auth/logout`
- **FR Reference:** FR-AUTH-003
- **Success Response (204 No Content)**

### 3.3 Request Password Reset (Public)
Sends a password reset link to the registered email.

- **Endpoint:** `POST /auth/password-reset-request`
- **FR Reference:** FR-AUTH-004
- **Request Body:**
```json
{
  "email": "user@example.com"
}
```
- **Success Response (200 OK):**
```json
{
  "message": "Password reset link sent to your email."
}
```

### 3.4 Reset Password (Public)
Resets the password using a valid token.

- **Endpoint:** `POST /auth/password-reset`
- **FR Reference:** FR-AUTH-004
- **Request Body:**
```json
{
  "email": "user@example.com",
  "token": "reset-token-received-via-email",
  "password": "new_secure_password",
  "password_confirmation": "new_secure_password"
}
```
- **Success Response (200 OK):**
```json
{
  "message": "Password successfully reset."
}
```

### 3.5 List Users
Retrieves a paginated list of users.

- **Endpoint:** `GET /users`
- **Permissions:** System Administrator, Auditor
- **Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "roles": ["payroll_officer"],
      "is_active": true,
      "created_at": "2026-06-30T10:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 5, "total": 45 }
}
```

### 3.6 Create User
Creates a new user and assigns roles.

- **Endpoint:** `POST /users`
- **Permissions:** System Administrator
- **FR Reference:** FR-AUTH-008, FR-AUTH-005
- **Request Body:**
```json
{
  "email": "newuser@example.com",
  "password": "initial_password",
  "password_confirmation": "initial_password",
  "roles": ["hr_officer"]
}
```
- **Success Response (201 Created)**

### 3.7 Update User Roles
Updates roles assigned to an existing user.

- **Endpoint:** `PUT /users/{id}/roles`
- **Permissions:** System Administrator
- **FR Reference:** FR-AUTH-005
- **Request Body:**
```json
{
  "roles": ["finance_manager", "payroll_officer"]
}
```
- **Success Response (200 OK)**

### 3.8 Deactivate User
Soft-deletes a user account.

- **Endpoint:** `DELETE /users/{id}`
- **Permissions:** System Administrator
- **FR Reference:** FR-AUTH-007
- **Success Response (204 No Content)**

---

## 4. Company Management (Module 4.02)

### 4.1 Get Company Profile
- **Endpoint:** `GET /company`
- **Permissions:** All authenticated users
- **Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "Acme Corp Tanzania",
    "tin": "123-456-789",
    "registration_number": "REG98765",
    "address": "123 Business Park, Dar es Salaam",
    "working_days_per_month": 21,
    "financial_year_start_month": 1,
    "sdl_enabled": true,
    "wcf_enabled": true,
    "sdl_employee_threshold": 4
  }
}
```

### 4.2 Update Company Profile
- **Endpoint:** `PUT /company`
- **Permissions:** System Administrator
- **FR Reference:** FR-COMP-001, FR-COMP-005, FR-COMP-006, FR-COMP-008
- **Request Body:** Matches the structure of the GET response data object.
- **Success Response (200 OK)**

### 4.3 List Branches
- **Endpoint:** `GET /branches`
- **Permissions:** All authenticated users
- **Success Response (200 OK)**

### 4.4 Create Branch
- **Endpoint:** `POST /branches`
- **Permissions:** System Administrator
- **FR Reference:** FR-COMP-002
- **Request Body:**
```json
{
  "code": "DAR-01",
  "name": "Dar es Salaam HQ"
}
```
- **Success Response (201 Created)**

### 4.5 List Departments
- **Endpoint:** `GET /departments`
- **Permissions:** All authenticated users
- **Success Response (200 OK)**

### 4.6 Create Department
- **Endpoint:** `POST /departments`
- **Permissions:** System Administrator
- **FR Reference:** FR-COMP-003
- **Request Body:**
```json
{
  "code": "HR-01",
  "name": "Human Resources",
  "branch_id": 1
}
```
- **Success Response (201 Created)**

### 4.7 List Cost Centers
- **Endpoint:** `GET /cost-centers`
- **Permissions:** All authenticated users
- **Success Response (200 OK)**

### 4.8 List Public Holidays
- **Endpoint:** `GET /public-holidays`
- **Permissions:** All authenticated users
- **Success Response (200 OK)**

### 4.9 Add Public Holiday
- **Endpoint:** `POST /public-holidays`
- **Permissions:** System Administrator
- **FR Reference:** FR-COMP-007
- **Request Body:**
```json
{
  "date": "2026-12-09",
  "name": "Independence Day"
}
```
- **Success Response (201 Created)**

---

## 5. Employee Management (Module 4.03)

### 5.1 List Employees
- **Endpoint:** `GET /employees`
- **Permissions:** HR Manager, HR Officer, Payroll Officer, Finance Manager, Auditor
- **Query Parameters:** `status`, `department_id`, `branch_id`, `search`
- **Success Response (200 OK):** Paginated list of employees.

### 5.2 Get Employee Profile
- **Endpoint:** `GET /employees/{id}`
- **Permissions:** Same as List, plus the Employee themselves.
- **Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "employee_number": "EMP001",
    "first_name": "John",
    "last_name": "Doe",
    "status": "active",
    "job_title": "Software Engineer",
    "department_id": 2,
    "branch_id": 1,
    "hire_date": "2024-01-15",
    "tin": "111-222-333",
    "resident_status": "resident",
    "secondary_employment_flag": false,
    "bank_details": {
      "bank_name": "CRDB Bank",
      "branch_code": "012",
      "account_number": "015XXXXXXX",
      "is_primary": true
    },
    "scheme_enrollments": [
      {
        "scheme_code": "nssf_employee",
        "membership_number": "NSSF987654",
        "effective_from": "2024-01-15"
      }
    ]
  }
}
```

### 5.3 Create Employee
- **Endpoint:** `POST /employees`
- **Permissions:** HR Manager, HR Officer
- **FR Reference:** FR-EMP-001
- **Request Body:** Employee details, bank info, scheme enrollments.
- **Success Response (201 Created)**

### 5.4 Update Employee Details
- **Endpoint:** `PUT /employees/{id}`
- **Permissions:** HR Manager, HR Officer
- **FR Reference:** FR-EMP-002, FR-EMP-003, FR-EMP-004
- **Success Response (200 OK)**

### 5.5 Terminate Employee
- **Endpoint:** `POST /employees/{id}/terminate`
- **Permissions:** HR Manager
- **FR Reference:** FR-EMP-009
- **Request Body:**
```json
{
  "termination_date": "2026-07-31",
  "reason": "Resignation",
  "acknowledge_loans": true
}
```
- **Success Response (200 OK)**

### 5.6 Reactivate Employee
- **Endpoint:** `POST /employees/{id}/reactivate`
- **Permissions:** HR Manager
- **FR Reference:** FR-EMP-010
- **Request Body:** `{"hire_date": "2026-08-01"}`
- **Success Response (200 OK)**

---

## 6. Salary Structure Management (Module 4.04)

### 6.1 List Salary Structures
- **Endpoint:** `GET /salary-structures`
- **Permissions:** HR Manager, Payroll Officer
- **Success Response (200 OK)**

### 6.2 Create Salary Structure
- **Endpoint:** `POST /salary-structures`
- **Permissions:** HR Manager
- **FR Reference:** FR-SAL-001
- **Request Body:** `{"code": "GRD-A", "name": "Grade A Executive"}`
- **Success Response (201 Created)**

### 6.3 Update Employee Basic Salary
- **Endpoint:** `POST /employees/{id}/salary`
- **Permissions:** HR Manager
- **FR Reference:** FR-SAL-003
- **Request Body:**
```json
{
  "basic_salary_amount": 1500000,
  "salary_structure_id": 1,
  "effective_from": "2026-07-01"
}
```
- **Success Response (201 Created):** Creates a new salary history record (append-only).

### 6.4 Get Employee Salary History
- **Endpoint:** `GET /employees/{id}/salary-history`
- **Permissions:** HR Manager, Payroll Officer
- **FR Reference:** FR-SAL-004
- **Success Response (200 OK):** List of historical basic salary records.

---

## 7. Earnings Management (Module 4.05)

### 7.1 List Earning Types
- **Endpoint:** `GET /earning-types`
- **Permissions:** Payroll Officer, HR Manager
- **Success Response (200 OK)**

### 7.2 Create Earning Type
- **Endpoint:** `POST /earning-types`
- **Permissions:** Payroll Officer, HR Manager
- **FR Reference:** FR-EARN-001
- **Request Body:**
```json
{
  "code": "ALW-HSE",
  "name": "Housing Allowance",
  "is_taxable": true,
  "is_pensionable": false,
  "recurrence": "recurring"
}
```
- **Success Response (201 Created)**

### 7.3 Assign Earning to Employee
- **Endpoint:** `POST /employees/{id}/earnings`
- **Permissions:** Payroll Officer, HR Manager (recurring); HR Officer (variable)
- **FR Reference:** FR-EARN-004, FR-EARN-006
- **Request Body:**
```json
{
  "earning_type_id": 2,
  "amount": 200000,
  "effective_from": "2026-07-01",
  "payroll_period_id": null
}
```
*Note: `payroll_period_id` is required if it's a one-time variable earning.*
- **Success Response (201 Created)**

---

## 8. Deductions Management (Module 4.06)

### 8.1 List Deduction Types
- **Endpoint:** `GET /deduction-types`
- **Permissions:** Payroll Officer
- **Success Response (200 OK)**

### 8.2 Create Deduction Type
- **Endpoint:** `POST /deduction-types`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-DED-001
- **Request Body:**
```json
{
  "code": "DED-SACCO",
  "name": "SACCO Contribution",
  "basis": "fixed_amount",
  "priority_tier": "tier_4"
}
```
- **Success Response (201 Created)**

### 8.3 Assign Deduction to Employee
- **Endpoint:** `POST /employees/{id}/deductions`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-DED-004, FR-DED-006
- **Request Body:**
```json
{
  "deduction_type_id": 3,
  "amount": 50000,
  "percentage": null,
  "effective_from": "2026-07-01",
  "payroll_period_id": null
}
```
- **Success Response (201 Created)**

---

## 9. Statutory Compliance Configuration (Module 4.07)

### 9.1 Get Statutory Configurations
- **Endpoint:** `GET /statutory/configurations`
- **Permissions:** System Administrator, Auditor, Payroll Officer, Finance Manager
- **Query Parameters:** `date` (defaults to current date for resolving active configuration)
- **Success Response (200 OK):**
```json
{
  "data": [
    {
      "code": "nssf_employee",
      "rate_percentage": 10.0,
      "effective_from": "2018-07-01"
    },
    {
      "code": "sdl",
      "rate_percentage": 4.0,
      "minimum_headcount": 4,
      "effective_from": "2021-07-01"
    }
  ]
}
```

### 9.2 Add Statutory Configuration Version
- **Endpoint:** `POST /statutory/configurations`
- **Permissions:** System Administrator
- **FR Reference:** FR-STAT-002
- **Request Body:**
```json
{
  "code": "wcf",
  "rate_percentage": 0.6,
  "effective_from": "2026-07-01"
}
```
- **Success Response (201 Created)**

### 9.3 Get PAYE Brackets
- **Endpoint:** `GET /statutory/paye-brackets`
- **Permissions:** System Administrator, Auditor, Payroll Officer, Finance Manager
- **Query Parameters:** `date`
- **Success Response (200 OK):** Array of active tax brackets based on the given date.

### 9.4 Add PAYE Bracket Set
- **Endpoint:** `POST /statutory/paye-brackets/bulk`
- **Permissions:** System Administrator
- **FR Reference:** FR-STAT-001
- **Request Body:**
```json
{
  "effective_from": "2026-07-01",
  "brackets": [
    { "minimum_income": 0, "maximum_income": 270000, "rate_percentage": 0, "base_tax_amount": 0 },
    { "minimum_income": 270000, "maximum_income": 520000, "rate_percentage": 8, "base_tax_amount": 0 }
  ]
}
```
*Note: The engine applies a strict `> minimum_income` comparison. Storing `270000` (not `270000.01` or `270001`) as the bracket floor preserves the business rule from `01_BUSINESS_RULES.md §2` and avoids the 1-TZS off-by-one failure mode.*
- **Success Response (201 Created)**

---

## 10. Leave Management (Module 4.08)

### 10.1 List Leave Records
- **Endpoint:** `GET /leave`
- **Permissions:** HR Manager, HR Officer
- **Success Response (200 OK):** Paginated list of leave records.

### 10.2 Record Leave
- **Endpoint:** `POST /leave`
- **Permissions:** HR Manager, HR Officer
- **FR Reference:** FR-LEAV-002
- **Request Body:**
```json
{
  "employee_id": 1,
  "leave_type": "unpaid",
  "start_date": "2026-07-10",
  "end_date": "2026-07-11",
  "days": 2
}
```
- **Success Response (201 Created)**

### 10.3 Update Leave Record
- **Endpoint:** `PUT /leave/{id}`
- **Permissions:** HR Manager, HR Officer
- **FR Reference:** FR-LEAV-003
- **Success Response (200 OK)**

### 10.4 Delete Leave Record
- **Endpoint:** `DELETE /leave/{id}`
- **Permissions:** HR Manager
- **FR Reference:** FR-LEAV-004
- **Success Response (204 No Content)**

---

## 11. Attendance & Overtime (Module 4.09)

### 11.1 List Overtime & Absence Records
- **Endpoint:** `GET /attendance`
- **Permissions:** HR Manager, HR Officer
- **Success Response (200 OK):** Paginated list of records.

### 11.2 Record Overtime
- **Endpoint:** `POST /attendance/overtime`
- **Permissions:** HR Manager, HR Officer
- **FR Reference:** FR-ATT-001, FR-ATT-002
- **Request Body:**
```json
{
  "employee_id": 1,
  "date": "2026-07-15",
  "hours": 4,
  "fixed_amount": null
}
```
*Note: Provide either `hours` or `fixed_amount`.*
- **Success Response (201 Created)**

### 11.3 Record Unauthorized Absence
- **Endpoint:** `POST /attendance/absence`
- **Permissions:** HR Manager, HR Officer
- **FR Reference:** FR-ATT-005
- **Request Body:**
```json
{
  "employee_id": 1,
  "days": 1,
  "date": "2026-07-20"
}
```
- **Success Response (201 Created)**

### 11.4 Delete Record
- **Endpoint:** `DELETE /attendance/{id}`
- **Permissions:** HR Manager
- **FR Reference:** FR-ATT-004
- **Success Response (204 No Content)**

---

## 12. Loan Management (Module 4.10)

### 12.1 List Loans
- **Endpoint:** `GET /loans`
- **Permissions:** HR Manager, Finance Manager, Payroll Officer
- **Success Response (200 OK)**

### 12.2 Register Loan
- **Endpoint:** `POST /loans`
- **Permissions:** HR Manager, Finance Manager
- **FR Reference:** FR-LOAN-001
- **Request Body:**
```json
{
  "employee_id": 1,
  "loan_type": "company_loan",
  "total_amount": 1000000,
  "installment_amount": 100000,
  "start_period_id": 5
}
```
- **Success Response (201 Created)**

### 12.3 Get Loan Details
- **Endpoint:** `GET /loans/{id}`
- **Permissions:** HR Manager, Finance Manager, Payroll Officer
- **FR Reference:** FR-LOAN-002
- **Success Response (200 OK):** Loan summary and installment history.

### 12.4 Update Loan Installment
- **Endpoint:** `PUT /loans/{id}/installment`
- **Permissions:** HR Manager, Finance Manager
- **FR Reference:** FR-LOAN-003
- **Request Body:**
```json
{
  "installment_amount": 150000
}
```
- **Success Response (200 OK)**

### 12.5 Suspend Loan Deduction
- **Endpoint:** `POST /loans/{id}/suspend`
- **Permissions:** Finance Manager
- **FR Reference:** FR-LOAN-004
- **Request Body:**
```json
{
  "payroll_period_id": 6
}
```
- **Success Response (200 OK)**

### 12.6 Close Loan Early
- **Endpoint:** `POST /loans/{id}/close`
- **Permissions:** Finance Manager
- **FR Reference:** FR-LOAN-005
- **Success Response (200 OK)**

---

## 13. Payroll Period Management (Module 4.11)

### 13.1 List Payroll Periods
- **Endpoint:** `GET /payroll-periods`
- **Permissions:** All authenticated users
- **FR Reference:** FR-PER-004
- **Success Response (200 OK):** List of periods (e.g., Draft, Locked, Filed).

### 13.2 Create Payroll Period
- **Endpoint:** `POST /payroll-periods`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-PER-001, FR-PER-002, FR-PER-003
- **Request Body:**
```json
{
  "name": "July 2026",
  "start_date": "2026-07-01",
  "end_date": "2026-07-31"
}
```
- **Success Response (201 Created)**

### 13.3 Reopen Closed Period
- **Endpoint:** `POST /payroll-periods/{id}/reopen`
- **Permissions:** System Administrator
- **FR Reference:** FR-PER-006
- **Request Body:**
```json
{
  "justification": "Incorrect deduction discovered post-lock; approval required for correction."
}
```
- **Success Response (200 OK)**

---

## 14. Payroll Processing Engine (Module 4.12)

### 14.1 Initialize Payroll Run
- **Endpoint:** `POST /payroll-runs`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-PROC-001
- **Request Body:**
```json
{
  "payroll_period_id": 5,
  "type": "standard"
}
```
*Note: `type` must match the `payroll_run_type` ENUM defined in `03_DATABASE_SPECIFICATION.md`: `standard | supplementary | amended_return`.*
- **Success Response (201 Created):** Creates run in `draft` status (stored lowercase as defined by the `payroll_run_status` ENUM).

### 14.2 Validate Run (Pre-flight)
- **Endpoint:** `POST /payroll-runs/{id}/validate`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-PROC-005
- **Success Response (200 OK):** Run status updated to `validated` (lowercase, stored ENUM value).

### 14.3 Calculate Run
- **Endpoint:** `POST /payroll-runs/{id}/calculate`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-PROC-006, FR-PROC-007
- **Success Response (200 OK):** Run status updated to `preview` (lowercase). Response body includes a `flags` array listing any employees with insufficient-funds warnings (FR-PROC-007). These must be resolved before the run may be submitted.
```json
{
  "data": {
    "status": "preview",
    "flags": [
      { "employee_id": 12, "issue": "net_salary_negative", "detail": "Total deductions exceed net pool by 45,000 TZS" }
    ]
  }
}
```

### 14.4 Get Preview Flags
- **Endpoint:** `GET /payroll-runs/{id}/flags`
- **Permissions:** Payroll Officer, Finance Manager
- **FR Reference:** FR-PROC-007
- **Success Response (200 OK):** Returns the current list of unresolved preview flags (employee-level insufficient-funds or data-error issues) blocking submission.

### 14.5 Submit Run for Approval
- **Endpoint:** `POST /payroll-runs/{id}/submit`
- **Permissions:** Payroll Officer (Maker)
- **FR Reference:** FR-PROC-008
- **Business Rule:** Submission is blocked if any unresolved ERROR-level flags remain from the Calculate step (FR-PROC-007). Records `submitted_by_user_id` for Maker/Checker enforcement.
- **Success Response (200 OK):** Run status updated to `approved` (lowercase ENUM value). This is the `preview → approved` transition; the submitting user's ID is captured as the Maker.

### 14.6 Approve & Lock Run
- **Endpoint:** `POST /payroll-runs/{id}/approve`
- **Permissions:** Finance Manager (Checker)
- **FR Reference:** FR-PROC-009, FR-PROC-015
- **Business Rule:** This is the `approved → locked` transition. The authenticated Finance Manager must **not** be the same user who submitted the run (`submitted_by_user_id`). Violation returns `403 Forbidden`. On success, captures `approved_by_user_id`, generates immutable `payroll_run_results` and `payslip_line_items` snapshot records, and locks all associated payslips as Hard Records.
- **Success Response (200 OK):** Run status updated to `locked` (lowercase ENUM value).

### 14.7 Reject Run
- **Endpoint:** `POST /payroll-runs/{id}/reject`
- **Permissions:** Finance Manager
- **FR Reference:** FR-PROC-011
- **Request Body:**
```json
{
  "reason": "Missing housing allowances for IT department."
}
```
- **Success Response (200 OK):** Run status reverted to `draft` (lowercase ENUM value).

### 14.8 Mark Run as Filed
- **Endpoint:** `POST /payroll-runs/{id}/file`
- **Permissions:** Finance Manager
- **FR Reference:** FR-PROC-008
- **Success Response (200 OK):** Run status updated to `filed` (lowercase ENUM value).

### 14.9 Reverse Run
- **Endpoint:** `POST /payroll-runs/{id}/reverse`
- **Permissions:** System Administrator
- **FR Reference:** FR-PROC-012
- **Request Body:**
```json
{
  "justification": "Incorrect bank export data."
}
```
- **Success Response (200 OK):** Run status updated to `reversed` (lowercase ENUM value).

### 14.10 Initiate Amended Return
- **Endpoint:** `POST /payroll-runs/{id}/amend`
- **Permissions:** System Administrator
- **FR Reference:** FR-PROC-014
- **Business Rule:** Source run must have status `filed`. Creates a new linked payroll run with type `amended_return` referencing the original run's ID.
- **Success Response (201 Created):** Creates a linked Amended run in `draft` status.

### 14.11 Initialize Supplementary Run
- **Endpoint:** `POST /payroll-runs`
- **Permissions:** Payroll Officer
- **FR Reference:** FR-PROC-013
- **Request Body:**
```json
{
  "payroll_period_id": 5,
  "type": "supplementary",
  "employee_ids": [12, 34, 56]
}
```
*Note: `type` must be `supplementary`. `employee_ids` is required for supplementary runs and restricts processing to the specified subset. The target period must have a status of `locked` or `filed`.*
- **Success Response (201 Created):** Creates a supplementary run in `draft` status scoped to the provided employees.

---

## 15. Payslip Management (Module 4.13)

### 15.1 Get Employee Payslips
- **Endpoint:** `GET /payslips`
- **Permissions:** Employee (own), HR Manager, Finance Manager, Payroll Officer, Auditor (all)
- **FR Reference:** FR-SLIP-003, FR-SLIP-004
- **Query Parameters:** `employee_id` (admin only), `payroll_period_id`
- **Success Response (200 OK):** List of available payslips.

### 15.2 Get Payslip Details
- **Endpoint:** `GET /payslips/{id}`
- **Permissions:** Same as above
- **FR Reference:** FR-SLIP-001
- **Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "employee_id": 1,
    "payroll_period_id": 5,
    "gross_salary": 2000000,
    "total_taxable_earnings": 2000000,
    "paye_amount": 250000,
    "nssf_employee_amount": 200000,
    "total_pre_tax_deductions": 200000,
    "total_post_tax_deductions": 50000,
    "net_salary": 1500000,
    "rounding_adjustment": 0.50,
    "earnings_breakdown": [],
    "deductions_breakdown": []
  }
}
```

### 15.3 Export Payslip PDF
- **Endpoint:** `GET /payslips/{id}/export`
- **Permissions:** Same as above
- **FR Reference:** FR-SLIP-002
- **Success Response (200 OK):** Binary PDF file.

---

## 16. Bank Export (Module 4.14)

### 16.1 Validate Bank Export Data
- **Endpoint:** `GET /bank-export/validate`
- **Permissions:** Finance Manager
- **FR Reference:** FR-BANK-002
- **Query Parameters:** `payroll_period_id`
- **Success Response (200 OK):**
```json
{
  "is_valid": true,
  "missing_bank_details": []
}
```

### 16.2 Download Bank Export CSV
- **Endpoint:** `GET /bank-export/download`
- **Permissions:** Finance Manager
- **FR Reference:** FR-BANK-001, FR-BANK-003
- **Query Parameters:** `payroll_period_id`
- **Success Response (200 OK):** Binary CSV file.

---

## 17. Reports (Module 4.15)

### 17.1 Generate Report
- **Endpoint:** `GET /reports/{report_type}`
- **Permissions:** HR Manager, Finance Manager, Finance Officer, Auditor
- **FR Reference:** FR-REP-001 to FR-REP-012
- **Path Parameters:**
  - `report_type`: e.g., `payroll-register`, `paye`, `nssf`, `sdl`, `wcf`, `loan-summary`, etc.
- **Query Parameters:** `payroll_period_id`, `format` (json, pdf, excel)
- **Success Response (200 OK):** Either JSON data or binary file download based on format requested.

---

## 18. Notifications (Module 4.16)

*Note: Notifications are primarily system-generated events (e.g., email dispatch) rather than REST endpoints called by clients.*

---

## 19. Audit Logs (Module 4.17)

### 19.1 List Audit Logs
- **Endpoint:** `GET /audit-logs`
- **Permissions:** System Administrator, Auditor
- **FR Reference:** FR-AUD-005
- **Query Parameters:** `user_id`, `table_name`, `event_type`, `start_date`, `end_date`
- **Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 100,
      "user_id": 2,
      "table_name": "payroll_runs",
      "record_id": 5,
      "event_type": "state_transition",
      "old_values": { "status": "approved" },
      "new_values": { "status": "locked" },
      "ip_address": "192.168.1.5",
      "created_at": "2026-07-28T14:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 50, "total": 1000 }
}
```
*Note: Field names (`table_name`, `record_id`, `old_values`, `new_values`) match the `audit_logs` table column definitions in `03_DATABASE_SPECIFICATION.md §5.13` exactly.*

---

## 20. System Settings (Module 4.18)

### 20.1 Get Notification Settings
- **Endpoint:** `GET /settings/notifications`
- **Permissions:** System Administrator
- **FR Reference:** FR-SET-002
- **Success Response (200 OK):** SMTP and provider config details.

### 20.2 Update Notification Settings
- **Endpoint:** `PUT /settings/notifications`
- **Permissions:** System Administrator
- **FR Reference:** FR-SET-002
- **Success Response (200 OK)**

### 20.3 Get System Health
- **Endpoint:** `GET /settings/health`
- **Permissions:** System Administrator
- **FR Reference:** FR-SET-003
- **Success Response (200 OK):** Status of queues, DB connection, failed jobs.
