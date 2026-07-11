# 02_FUNCTIONAL_REQUIREMENTS.md
# Functional Requirements Specification
## Tanzanian Payroll & HR Management System

---

| Attribute       | Value                                              |
|-----------------|----------------------------------------------------|
| Document ID     | 02_FUNCTIONAL_REQUIREMENTS                         |
| Version         | 1.1                                                |
| Status          | Draft                                              |
| Owner           | Product Management                                 |
| Relates To      | 00_SPEC.md, 01_BUSINESS_RULES.md                   |
| Feeds Into      | `03_DATABASE_SPECIFICATION.md`, `04_SYSTEM_ARCHITECTURE.md`, `06_UI_SPECIFICATION.md`, `11_NON_FUNCTIONAL_REQUIREMENTS.md` |

---

## Table of Contents

1. Introduction
2. Module Status Summary
3. User Roles & Permissions
4. Functional Modules
   - 4.01 Authentication & Authorization
   - 4.02 Company Management
   - 4.03 Employee Management
   - 4.04 Salary Structure Management
   - 4.05 Earnings Management
   - 4.06 Deductions Management
   - 4.07 Statutory Compliance Configuration
   - 4.08 Leave Management
   - 4.09 Attendance & Overtime
   - 4.10 Loan Management
   - 4.11 Payroll Period Management
   - 4.12 Payroll Processing Engine
   - 4.13 Payslip Management
   - 4.14 Bank Export
   - 4.15 Reports
   - 4.16 Notifications
   - 4.17 Audit Logs
   - 4.18 System Settings
5. Cross-Module Workflows
6. Search & Filtering
7. Import & Export
8. Business Events Registry
9. Out of Scope

---

## 1. Introduction

### 1.1 Purpose

This document defines the **functional requirements** for the MVP of a Tanzanian Payroll & HR Management System. It specifies *what* the system must do — describing modules, user interactions, inputs, outputs, workflows, and acceptance criteria.

This document does **not** define implementation technology, data structures, algorithms, or solution architecture. Those concerns belong in:
- `04_SYSTEM_ARCHITECTURE.md` — Technical stack, infrastructure, design patterns.
- `03_DATABASE_SPECIFICATION.md` — Schema, tables, relationships.
- `11_NON_FUNCTIONAL_REQUIREMENTS.md` — Performance, security, scalability.

### 1.2 Scope

The system serves a Tanzanian organization (target range: 20–500 employees). It covers the full payroll lifecycle from employee onboarding through to statutory filing, within the boundaries defined in `00_SPEC.md`.

### 1.3 Intended Audience

| Audience                    | Purpose                                                   |
|-----------------------------|-----------------------------------------------------------|
| Product Manager             | Backlog prioritization and sprint planning                |
| UX Designer                 | Screen and interaction design                             |
| Solutions Architect         | Architecture decisions and component design               |
| Software Engineers          | Feature implementation reference                          |
| QA Lead                     | Acceptance criteria and test case derivation              |
| Technical Writer            | User documentation and help content                       |
| External Auditor / Regulator| Compliance traceability                                   |

### 1.4 Definitions & Abbreviations

| Term           | Definition                                                                  |
|----------------|-----------------------------------------------------------------------------|
| MVP            | Minimum Viable Product — the initial production-ready release               |
| TRA            | Tanzania Revenue Authority                                                  |
| NSSF           | National Social Security Fund                                               |
| WCF            | Workers Compensation Fund                                                   |
| SDL            | Skills Development Levy                                                     |
| PAYE           | Pay As You Earn — progressive income tax                                    |
| Maker          | The user who initiates and prepares a payroll run                           |
| Checker        | A separate, authorized user who reviews and approves a payroll run          |
| Payroll Period | A defined time window (e.g., July 2026) that is the container for a run     |
| Run            | A single payroll computation event for a given period                       |
| Snapshot       | An immutable copy of calculation inputs stored at the time of locking       |
| FR-XXX-NNN     | Functional Requirement ID format used in this document                      |

### 1.5 Governing Documents

This document **must** be read alongside:

- `00_SPEC.md` — System scope, module list, and guiding principles.
- `01_BUSINESS_RULES.md` — Authoritative source for all payroll calculation logic, statutory rules, state machine definitions, and compliance constraints.

Where a requirement involves payroll logic, this document **references** the relevant section of `01_BUSINESS_RULES.md` rather than restating it.

---

## 2. Module Status Summary

This table defines what is included in the MVP, deferred to a future phase, or out of scope entirely.

| Module                          | MVP ✅ | Phase 2 ⏳ | Future 🔮 |
|---------------------------------|--------|------------|-----------|
| Authentication & Authorization  | ✅     |            |           |
| Company Management              | ✅     |            |           |
| Employee Management             | ✅     |            |           |
| Salary Structure Management     | ✅     |            |           |
| Earnings Management             | ✅     |            |           |
| Deductions Management           | ✅     |            |           |
| Statutory Compliance Config     | ✅     |            |           |
| Leave Management (Unpaid only)  | ✅     |            |           |
| Leave Request Workflows         |        | ⏳         |           |
| Attendance & Overtime (Manual)  | ✅     |            |           |
| Biometric Attendance            |        |            | 🔮        |
| Loan Management                 | ✅     |            |           |
| Interest-Bearing Loans          |        | ⏳         |           |
| Payroll Period Management       | ✅     |            |           |
| Payroll Processing Engine       | ✅     |            |           |
| Payslip Management              | ✅     |            |           |
| Bank Export (CSV)               | ✅     |            |           |
| Direct Bank API Integration     |        |            | 🔮        |
| Reports (Standard Set)          | ✅     |            |           |
| Custom Report Builder           |        | ⏳         |           |
| Notifications (Email)           | ✅     |            |           |
| Notifications (SMS)             |        | ⏳         |           |
| In-App Notification Bell        |        | ⏳         |           |
| Audit Logs                      | ✅     |            |           |
| System Settings                 | ✅     |            |           |
| Employee Self-Service Portal    |        | ⏳         |           |
| Recruitment Module              |        |            | 🔮        |
| Performance Management          |        |            | 🔮        |
| Mobile Application              |        |            | 🔮        |

---

## 3. User Roles & Permissions

### 3.1 Role Definitions

| Role                | Alias      | Primary Responsibility                                            |
|---------------------|------------|-------------------------------------------------------------------|
| System Administrator| Sys Admin  | System configuration, user and role management                   |
| HR Manager          | HR Mgr     | Employee lifecycle oversight, leave approvals                     |
| HR Officer          | HR Off     | Daily HR data entry                                               |
| Payroll Officer     | **Maker**  | Payroll preparation: data entry, initiation, and submission       |
| Finance Manager     | **Checker**| Payroll approval, financial oversight, bank exports               |
| Finance Officer     | Fin Off    | Reporting, reconciliation support                                 |
| Department Manager  | Dept Mgr   | Department-scoped visibility                                      |
| Employee            | Emp        | Read-only access to own profile and payslips                      |
| Auditor             | Audit      | Read-only access to audit logs, reports, and configurations       |

### 3.2 Permission Matrix

| Capability                        | Sys Admin | HR Mgr | HR Off | Maker | Checker | Dept Mgr | Emp | Audit |
|-----------------------------------|:---------:|:------:|:------:|:-----:|:-------:|:--------:|:---:|:-----:|
| Manage Users & Roles              | ✅        |        |        |       |         |          |     |       |
| Manage Company & Branches         | ✅        | View   |        |       | View    |          |     | View  |
| Create / Edit Employees           | ✅        | ✅     | ✅     |       |         |          |     |       |
| View Employees (all)              | ✅        | ✅     | ✅     | ✅    | ✅      |          |     | ✅    |
| View Employees (own dept only)    |           |        |        |       |         | ✅       |     |       |
| Manage Earnings / Deductions      |           | ✅     |        | ✅    |         |          |     |       |
| Manage Loans                      |           | ✅     |        | ✅    | ✅      |          |     |       |
| Input Leave / Attendance Data     |           | ✅     | ✅     |       |         |          |     |       |
| Initiate / Calculate Payroll      |           |        |        | ✅    |         |          |     |       |
| Submit Payroll for Approval       |           |        |        | ✅    |         |          |     |       |
| Approve / Reject Payroll          |           |        |        |       | ✅      |          |     |       |
| Generate Bank Export              |           |        |        |       | ✅      |          |     |       |
| View All Reports                  | ✅        | ✅     |        | ✅    | ✅      |          |     | ✅    |
| View Own Payslip                  |           |        |        |       |         |          | ✅  |       |
| View Audit Logs                   | ✅        |        |        |       |         |          |     | ✅    |
| Manage System Settings            | ✅        |        |        |       |         |          |     |       |
| Reopen Closed Payroll Period      | ✅        |        |        |       |         |          |     |       |

> **Restriction (Enforced at System Level):** The same user account that submits a payroll run (Maker) cannot be the user that approves it (Checker). This constraint is non-negotiable. See `01_BUSINESS_RULES.md` — Section 9.3.

---

## 4. Functional Modules

> **Module Template Note:** Every module follows the structure below:
> *Purpose → Scope → Data Ownership → Users → Functional Requirements → Business Rules References → Preconditions → Inputs → Main Workflow → Alternative Flows → Outputs → Postconditions → Business Events → Permissions → Related Screens → Acceptance Criteria → Error Conditions → Dependencies → Future Enhancements*

---

### 4.01 Authentication & Authorization

#### Purpose
Control who can access the system, what they can do, and maintain an identity record for all actions.

#### Scope
User login, session management, password recovery, role assignment, and permission enforcement across all modules.

#### Data Ownership
System Administrator.

#### Users
All system users.

#### Functional Requirements

| ID           | Requirement                  | Description                                                                                   | Priority |
|--------------|------------------------------|-----------------------------------------------------------------------------------------------|----------|
| FR-AUTH-001  | User Login                   | The system shall authenticate users using a registered email address and password.            | High     |
| FR-AUTH-002  | Session Management           | The system shall maintain an authenticated session after successful login.                    | High     |
| FR-AUTH-003  | Logout                       | The system shall terminate a user session when the user explicitly logs out.                  | High     |
| FR-AUTH-004  | Password Reset               | The system shall allow users to reset their password via a time-limited, single-use link delivered to their registered email address. | High |
| FR-AUTH-005  | Role Assignment              | The system shall allow a System Administrator to assign one or more roles to a user account.  | High     |
| FR-AUTH-006  | Permission Enforcement       | The system shall enforce role-based access control on every page and action.                  | High     |
| FR-AUTH-007  | Account Deactivation         | The system shall allow a System Administrator to deactivate a user account, preventing future login. | High |
| FR-AUTH-008  | User Creation                | The system shall allow a System Administrator to create new user accounts and assign them roles. | High |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 9.3: Maker / Checker Rule (segregation of duties enforcement).
- `01_BUSINESS_RULES.md` — Section 8: Audit Trail requirements.

#### Preconditions
- None for login. User account must be Active.

#### Inputs
- Email address, Password.

#### Main Workflow
1. User navigates to the login screen.
2. User enters credentials and submits.
3. System validates identity.
4. System establishes a session and redirects to the Dashboard.

#### Alternative Flows
- **Invalid credentials:** System rejects login and displays a non-specific error message. No indication of which field is incorrect (security).
- **Deactivated account:** System rejects login with an appropriate message.
- **Password Reset:** User clicks "Forgot Password", enters registered email, receives a reset link, sets a new password.

#### Outputs
- Authenticated session.
- Password reset notification (Email).

#### Postconditions
- User is authenticated and operating within their assigned role's permissions.

#### Business Events

| Event                     | Trigger                                               |
|---------------------------|-------------------------------------------------------|
| `user.login.succeeded`    | Successful authentication.                            |
| `user.login.failed`       | Failed authentication attempt.                        |
| `user.logout`             | User terminates session.                              |
| `user.password.reset_requested` | Password reset link requested.               |
| `user.password.changed`   | Password successfully changed.                        |
| `user.created`            | New user account created by Admin.                    |
| `user.deactivated`        | User account deactivated by Admin.                    |
| `user.role.assigned`      | Role assigned or changed by Admin.                    |

#### Permissions

| Action               | Roles                         |
|----------------------|-------------------------------|
| Login / Logout       | All users                     |
| Reset own password   | All users                     |
| Create users         | System Administrator          |
| Assign roles         | System Administrator          |
| Deactivate accounts  | System Administrator          |

#### Related Screens
- Login Screen
- Password Reset Screen
- User Management List
- User Form (Create / Edit)

#### Acceptance Criteria
✓ A user with no role assignment shall be denied access to all protected screens.
✓ A deactivated user shall not be able to log in.
✓ The same user account that submits a payroll run shall not be permitted to approve that same run.
✓ A password reset link shall expire and become invalid after use or after a defined time limit.

#### Error Conditions
- Invalid credentials → Generic error, no field-level disclosure.
- Deactivated account → "Account disabled. Contact your administrator."
- Expired reset link → "This link is no longer valid. Please request a new reset link."

#### Dependencies
- Email notification service (see Module 4.16).
- Audit Logs (see Module 4.17).

#### Future Enhancements
- Multi-Factor Authentication (MFA).
- Single Sign-On (SSO) support.

---

### 4.02 Company Management

#### Purpose
Define and maintain the organizational structure and company-wide payroll configuration.

#### Scope
Company profile, branch management, department management, cost center management, and payroll calendar settings.

#### Data Ownership
System Administrator (configuration); HR Manager (department structure).

#### Users
System Administrator, HR Manager (view), Finance Manager (view).

#### Functional Requirements

| ID           | Requirement                    | Description                                                                                          | Priority |
|--------------|--------------------------------|------------------------------------------------------------------------------------------------------|----------|
| FR-COMP-001  | Manage Company Profile         | The system shall allow the Administrator to record and update the company name, TIN, legal address, and registration number. | High |
| FR-COMP-002  | Manage Branches                | The system shall allow the creation, editing, and logical deletion of Branch records.                | High     |
| FR-COMP-003  | Manage Departments             | The system shall allow the creation, editing, and logical deletion of Departments, linked to a Branch. | High   |
| FR-COMP-004  | Manage Cost Centers            | The system shall allow the creation, editing, and logical deletion of Cost Centers, linked to a Branch or Department. | Medium |
| FR-COMP-005  | Configure Working Days         | The system shall provide a global setting for the standard number of working days per month.         | High     |
| FR-COMP-006  | Configure Financial Year       | The system shall allow the Administrator to define the start and end months of the company's financial year. | High |
| FR-COMP-007  | Configure Public Holidays      | The system shall provide an interface to add, edit, and remove public holidays by calendar year, ensuring working day calculations remain accurate. | High |
| FR-COMP-008  | Configure Statutory Exemptions | The system shall allow enabling or disabling company-level statutory contributions (e.g., SDL, WCF) for organizations with legal exemptions. | High |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 6: Working Days Configuration (universal denominator for proration).
- `01_BUSINESS_RULES.md` — Section 3: Employer Level Exemptions (SDL/WCF toggles).
- `01_BUSINESS_RULES.md` — Section 10: Public Holiday Maintenance.

#### Preconditions
- System Administrator role required.

#### Inputs
- Company legal name, TIN, address, registration number.
- Branch name, city, and address.
- Department name, parent Branch.
- Working days integer.
- Public holiday dates and descriptions.

#### Main Workflow
1. Administrator navigates to Settings → Company Profile and fills in company details.
2. Navigates to Branches and creates the organizational branches.
3. Navigates to Departments and creates departments linked to branches.
4. Configures the global working days and financial year settings.

#### Alternative Flows
- **Deleting a Branch/Department with active employees:** The system shall block the deletion and notify the user that active employee assignments must be resolved first.

#### Outputs
- Updated company configuration.
- Public holiday calendar applied to payroll calculations.

#### Postconditions
- Organizational structure is available for employee assignment.
- Working day configuration is applied to all proration calculations.

#### Business Events

| Event                         | Trigger                                          |
|-------------------------------|--------------------------------------------------|
| `company.profile.updated`     | Company details changed.                         |
| `branch.created`              | New branch added.                                |
| `branch.deactivated`          | Branch marked inactive.                          |
| `department.created`          | New department added.                            |
| `department.deactivated`      | Department marked inactive.                      |
| `public_holiday.created`      | Holiday added to calendar.                       |
| `settings.working_days.changed`| Standard working days setting updated.          |

#### Permissions

| Action                        | Roles                     |
|-------------------------------|---------------------------|
| View                          | All authenticated users   |
| Create / Edit / Delete        | System Administrator      |

#### Related Screens
- Company Profile Screen
- Branch List & Form
- Department List & Form
- Cost Center List & Form
- System Settings Screen
- Public Holiday Calendar

#### Acceptance Criteria
✓ A Branch or Department with active employee assignments shall not be deletable.
✓ The working days setting shall be a required field and validated as a positive integer.
✓ Disabling SDL at the company level shall cause all subsequent payroll runs to yield SDL = 0 for all employees.

#### Error Conditions
- Attempted deletion of a Branch/Department with active employees → "Cannot delete: active employee assignments exist."

#### Dependencies
- Employee Management (Module 4.03) — employees reference Branches and Departments.
- Payroll Processing Engine (Module 4.12) — uses working days and public holiday settings.

#### Future Enhancements
- Multi-company / holding company support.
- Automated public holiday imports from a national calendar feed.

---

### 4.03 Employee Management

#### Purpose
Maintain comprehensive, accurate employee records as the single source of truth for all HR and payroll processing.

#### Scope
Employee lifecycle from hiring through termination, including personal data, employment details, statutory identifiers, banking details, and emergency contacts.

#### Data Ownership
HR Manager (primary); Payroll Officer (read access for payroll inputs).

#### Users
HR Manager, HR Officer (Create/Edit); Payroll Officer (View); Finance Manager (View).

#### Functional Requirements

| ID          | Requirement                        | Description                                                                                                           | Priority |
|-------------|------------------------------------|-----------------------------------------------------------------------------------------------------------------------|----------|
| FR-EMP-001  | Create Employee                    | The system shall allow the creation of a new employee record with a unique, system-generated Employee Number.         | High     |
| FR-EMP-002  | Edit Employee Personal Details     | The system shall allow updating employee personal data (name, date of birth, gender, nationality, address, contact).  | High     |
| FR-EMP-003  | Manage Employment Details          | The system shall allow setting and updating Job Title, Department, Branch, Hire Date, and Employment Type.            | High     |
| FR-EMP-004  | Manage Statutory Profile           | The system shall allow recording TIN, resident status (Resident / Non-Resident), and secondary employment flag on the employee record. | High     |
| FR-EMP-005  | Manage Statutory Participation     | The system shall allow explicitly enrolling or exempting an employee from NSSF, pension, or other statutory schemes, recording the scheme membership number and an effective date for each enrollment. | High |
| FR-EMP-006  | Manage Bank Details                | The system shall allow recording bank account number, bank name, and branch code for salary disbursement.             | High     |
| FR-EMP-007  | Manage Emergency Contacts          | The system shall allow recording at least one emergency contact per employee.                                         | Medium   |
| FR-EMP-008  | Upload Employee Documents          | The system shall allow attaching documents (e.g., contract, ID) to an employee record.                               | Medium   |
| FR-EMP-009  | Terminate Employee                 | The system shall allow marking an employee as Terminated with an effective date, removing them from future payroll runs. If the employee has active loans with an outstanding balance, the system shall display a mandatory warning requiring HR acknowledgment before the termination is confirmed. | High |
| FR-EMP-010  | Reactivate Employee                | The system shall allow reactivating a previously terminated employee with a new Hire Date.                            | High     |
| FR-EMP-011  | View Employee History              | The system shall maintain and display a chronological log of key changes to the employee record (salary, department, status). | High |
| FR-EMP-012  | Search & Filter Employees          | The system shall allow searching employees by name, employee number, department, branch, and status.                  | High     |
| FR-EMP-013  | Export Employee List               | The system shall allow exporting the filtered employee list to a structured data format.                              | Medium   |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 3: Employee Participation Logic (explicit enrollment, effective dates, exemptions).
- `01_BUSINESS_RULES.md` — Section 2: Non-Resident flag and Secondary Employment flag.

#### Preconditions
- At least one Branch and one Department must exist (Module 4.02).

#### Inputs
- Personal details: first name, last name, date of birth, gender, nationality.
- Employment: hire date, job title, department, branch, employment type.
- Statutory: TIN, NSSF number, resident status (Resident / Non-Resident), secondary employment flag.
- Scheme enrollment: scheme name, participation status, effective date.
- Banking: account number, bank name, branch code.
- Termination: effective termination date.

#### Main Workflow
1. HR Officer navigates to Employees → Add New Employee.
2. Completes Personal, Employment, Statutory, Banking, and Scheme Enrollment tabs.
3. Saves the record.
4. System assigns a unique Employee Number.
5. Employee status is set to "Active".

#### Alternative Flows
- **Rehiring:** HR reactivates a terminated employee, providing a new hire date. Previous employment history is preserved.
- **Scheme change:** HR changes an employee's enrolled scheme, providing an effective date. The prior enrollment record is retained for historical payrolls.

#### Outputs
- Employee record.
- System-generated Employee Number.

#### Postconditions
- The employee is available for assignment to payroll runs.

#### Business Events

| Event                          | Trigger                                                         |
|--------------------------------|-----------------------------------------------------------------|
| `employee.created`             | New employee record saved.                                      |
| `employee.updated`             | Any field on the employee record changes.                       |
| `employee.activated`           | Employee status set to Active.                                  |
| `employee.terminated`          | Employee marked as Terminated.                                  |
| `employee.reactivated`         | Previously terminated employee reactivated.                     |
| `employee.department.changed`  | Employee assigned to a new Department.                          |
| `employee.scheme.changed`      | Statutory scheme enrollment updated.                            |
| `employee.bank_details.changed`| Banking information updated.                                    |

#### Permissions

| Action                        | Roles                              |
|-------------------------------|------------------------------------|
| View (all)                    | HR Manager, HR Officer, Payroll Officer, Finance Manager |
| Create / Edit                 | HR Manager, HR Officer             |
| Terminate / Reactivate        | HR Manager                         |
| Export                        | HR Manager, Finance Manager        |

#### Related Screens
- Employee List Screen
- Employee Profile Screen (Personal Tab)
- Employment Details Tab
- Statutory & Scheme Tab
- Banking Details Tab
- Documents Tab
- Salary History View
- Termination Form

#### Acceptance Criteria
✓ Employee Number shall be unique and system-generated; manual entry is not permitted.
✓ Duplicate TIN values across active employees shall be rejected by the system.
✓ Duplicate scheme membership numbers (e.g., NSSF numbers) across active employees for the same scheme shall be rejected — enforced via a unique partial index on `employee_scheme_enrollments.membership_number`.
✓ An employee flagged as **Secondary Employment** shall have their PAYE calculated at the flat secondary employment rate configured in Module 4.07 (FR-STAT-006), not the progressive brackets.
✓ A terminated employee shall not appear in new payroll runs opened after their termination date.
✓ Changes to statutory scheme enrollment shall capture an effective date and preserve the prior enrollment for historical payroll accuracy.
✓ Basic Salary history shall be immutably logged for audit purposes.

#### Error Conditions
- Duplicate Employee TIN → "An active employee with this TIN already exists."
- Duplicate NSSF Number → "An active employee with this NSSF number already exists."
- Missing required field → Field-level validation message.

#### Dependencies
- Company Management (Module 4.02) — Branches and Departments must exist.
- Salary Structure Management (Module 4.04) — Basic Salary is entered here.
- Payroll Processing Engine (Module 4.12) — consumes employee records.

#### Future Enhancements
- Bulk employee import via structured file upload.
- Automated document expiry alerts (contract renewal reminders).

---

### 4.04 Salary Structure Management

#### Purpose
Define and govern the salary frameworks that determine how employee compensation is structured and calculated.

#### Scope
Creation of configurable salary structures and pay grades, and assignment of employees to a structure.

#### Data Ownership
HR Manager, Finance Manager.

#### Users
HR Manager, Payroll Officer.

#### Functional Requirements

| ID          | Requirement                    | Description                                                                                            | Priority |
|-------------|--------------------------------|--------------------------------------------------------------------------------------------------------|----------|
| FR-SAL-001  | Create Salary Structure        | The system shall allow creating named salary structures (e.g., "Executive Grade", "Standard Grade").   | High     |
| FR-SAL-002  | Assign Employee to Structure   | The system shall allow assigning an employee to a salary structure with an effective date.             | High     |
| FR-SAL-003  | Record Basic Salary            | The system shall allow setting an employee's Basic Salary amount with a recorded effective date.       | High     |
| FR-SAL-004  | View Salary History            | The system shall display a full chronological history of Basic Salary changes per employee.            | High     |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 4: Basic Salary definition.
- `01_BUSINESS_RULES.md` — Section 6: Retroactive Salary Changes & Arrears Algorithm.

#### Preconditions
- Employee record must exist (Module 4.03).

#### Inputs
- Structure name.
- Employee ID, Basic Salary amount, effective date.

#### Main Workflow
1. Payroll Officer defines a salary structure.
2. HR Manager assigns an employee to the structure.
3. HR records the Basic Salary with an effective date.
4. System logs the change to the salary history.

#### Alternative Flows
- **Salary revision:** HR records a new salary amount with a new effective date. The old record is preserved.

#### Outputs
- Salary structure configuration.
- Employee salary history.

#### Postconditions
- The employee's current salary is used in the next payroll run.

#### Business Events

| Event                       | Trigger                                          |
|-----------------------------|--------------------------------------------------|
| `salary_structure.created`  | A new salary structure is defined.               |
| `employee.salary.changed`   | An employee's Basic Salary is updated.           |

#### Permissions

| Action            | Roles                            |
|-------------------|----------------------------------|
| View              | HR Manager, Payroll Officer      |
| Create Structures | HR Manager                       |
| Assign / Edit     | HR Manager                       |

#### Related Screens
- Salary Structures List & Form
- Employee Salary Tab (History View)

#### Acceptance Criteria
✓ Basic Salary changes shall be recorded with an effective date and preserved in an immutable history.
✓ A salary of zero is permitted only if explicitly confirmed by an authorized user.

#### Error Conditions
- Negative salary value entered → "Basic Salary cannot be negative."

#### Dependencies
- Employee Management (Module 4.03).
- Payroll Processing Engine (Module 4.12).

#### Future Enhancements
- Pay grade bands with automatic step progressions.

---

### 4.05 Earnings Management

#### Purpose
Configure all categories of employee income and capture variable earnings for each payroll period.

#### Scope
Earning type definitions (taxability, recurrence), assignment to employees, and period-specific input (bonuses, overtime amounts, one-time payments).

#### Data Ownership
Payroll Officer.

#### Users
Payroll Officer, HR Officer (overtime input), HR Manager.

#### Functional Requirements

| ID          | Requirement                     | Description                                                                                              | Priority |
|-------------|---------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-EARN-001 | Define Earning Type             | The system shall allow creating named earning types with a taxability flag (Taxable / Non-Taxable) and a pensionability flag (Pensionable / Non-Pensionable). | High     |
| FR-EARN-002 | Edit Earning Type               | The system shall allow editing the name, taxability, and pensionability classification of an earning type.                 | High     |
| FR-EARN-003 | Deactivate Earning Type         | The system shall allow logically deactivating an earning type, preventing its use in future runs without deleting historical data. | Medium |
| FR-EARN-004 | Assign Recurring Earning        | The system shall allow assigning a recurring earning to an employee with a fixed amount and an effective date. | High |
| FR-EARN-005 | Remove Recurring Earning        | The system shall allow removing a recurring earning from an employee, effective from a specified period.   | High     |
| FR-EARN-006 | Input Variable Earning          | The system shall allow entering a one-off earning (e.g., annual bonus) for a specific employee in the current open payroll period. | High |
| FR-EARN-007 | Delete Variable Earning         | The system shall allow deleting a variable earning from the current open period before the run is submitted. | High |
| FR-EARN-008 | Pensionable Earnings Base       | The system shall include all earnings flagged as `is_pensionable = true` in the NSSF/pension contribution calculation base (Total Gross Salary). | High |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 4: Earnings Rules (taxability, overtime formula, bonus taxation, one-time payments).

#### Preconditions
- An open payroll period must exist for variable earning entry (Module 4.11).
- Employee must be Active.

#### Inputs
- Earning type name, taxability flag.
- Employee ID, earning type, monetary amount, effective date (for recurring).
- Employee ID, earning type, amount (for variable, one-off input).

#### Main Workflow
1. Payroll Officer defines earning types (e.g., "Housing Allowance" — Non-Taxable).
2. Assigns recurring allowances to qualifying employees.
3. At period open, inputs any one-off bonuses or incentives.
4. Engine picks up all earnings on calculation.

#### Alternative Flows
- **Earning type deactivated mid-period:** Existing assignments in the current period continue; no new assignments permitted.

#### Outputs
- Earning line items included in payroll calculation.
- Earnings Summary Report.

#### Postconditions
- All relevant earnings are available to the payroll engine for the current period.

#### Business Events

| Event                              | Trigger                                                    |
|------------------------------------|------------------------------------------------------------|
| `earning_type.created`             | New earning type defined.                                  |
| `earning_type.deactivated`         | Earning type disabled.                                     |
| `employee.recurring_earning.added` | Recurring earning assigned to employee.                    |
| `employee.recurring_earning.removed`| Recurring earning removed from employee.                  |
| `payroll.variable_earning.added`   | One-off earning entered for current period.                |

#### Permissions

| Action                        | Roles                              |
|-------------------------------|------------------------------------|
| Define / Edit Earning Types   | Payroll Officer, HR Manager        |
| Assign / Remove Recurring     | Payroll Officer, HR Manager        |
| Input Variable Earnings       | Payroll Officer, HR Officer        |

#### Related Screens
- Earning Types List & Form
- Employee Earnings Tab
- Period Variable Earnings Form

#### Acceptance Criteria
✓ A newly created earning type shall default to Taxable unless explicitly set otherwise.
✓ Earning amounts shall be validated to be zero or greater; negative values shall be rejected.
✓ Non-taxable earnings shall be included in Total Gross Salary (for NSSF base) but excluded from Gross Taxable Salary (for PAYE).
✓ Deactivating an earning type shall not alter or remove earnings already recorded in locked payroll runs.

#### Error Conditions
- Negative earning amount → "Earning amount cannot be negative."
- Variable earning entered in a non-open period → "Cannot add earnings: the period is not in Draft state."

#### Dependencies
- Payroll Period Management (Module 4.11).
- Payroll Processing Engine (Module 4.12).
- Tanzania Statutory Compliance (Module 4.07).

#### Future Enhancements
- Bulk import of variable earnings via structured file upload.

---

### 4.06 Deductions Management

#### Purpose
Configure and capture all non-statutory deductions applied to employee net pay.

#### Scope
Deduction type definitions (priority tier, calculation basis), assignment to employees, and period-specific entries.

#### Data Ownership
Payroll Officer.

#### Users
Payroll Officer.

#### Functional Requirements

| ID          | Requirement                        | Description                                                                                              | Priority |
|-------------|------------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-DED-001  | Define Deduction Type              | The system shall allow creating named deduction types assigned to a Priority Tier.                       | High     |
| FR-DED-002  | Edit Deduction Type                | The system shall allow updating the name and priority of a deduction type.                               | High     |
| FR-DED-003  | Deactivate Deduction Type          | The system shall allow logically deactivating a deduction type without deleting historical records.      | Medium   |
| FR-DED-004  | Assign Recurring Deduction         | The system shall allow assigning a recurring deduction to an employee as either a fixed amount or a percentage of Basic Salary. | High |
| FR-DED-005  | Remove Recurring Deduction         | The system shall allow removing a recurring deduction from an employee from a specified period forward.  | High     |
| FR-DED-006  | Input Variable Deduction           | The system shall allow entering a one-off deduction for a specific employee in the current open period.  | High     |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 5: Deduction Rules (Canonical 5-Tier Priority Order, fixed vs. percentage, mandatory vs. optional).

#### Preconditions
- An open payroll period must exist for variable deduction entry.
- Employee must be Active.

#### Inputs
- Deduction type name, Priority Tier (1–5), calculation basis (Fixed / Percentage).
- Employee ID, deduction type, amount or percentage, effective date.

#### Main Workflow
1. Payroll Officer defines deduction types (e.g., "SACCO Deduction" — Tier 4).
2. Assigns recurring deductions to employees with specified amounts.
3. Engine applies deductions in Tier sequence during payroll calculation.

#### Alternative Flows
- **Insufficient net pool:** Detected during Payroll Preview. See Module 4.12 — FR-PROC-007.

#### Outputs
- Deduction line items on employee payslips.
- Deduction Summary Report.

#### Postconditions
- All configured deductions are applied in the correct priority order during payroll calculation.

#### Business Events

| Event                                 | Trigger                                              |
|---------------------------------------|------------------------------------------------------|
| `deduction_type.created`              | New deduction type defined.                          |
| `deduction_type.deactivated`          | Deduction type disabled.                             |
| `employee.recurring_deduction.added`  | Recurring deduction assigned to employee.            |
| `employee.recurring_deduction.removed`| Recurring deduction removed from employee.           |
| `payroll.variable_deduction.added`    | One-off deduction entered for current period.        |

#### Permissions

| Action                        | Roles              |
|-------------------------------|--------------------|
| Define / Edit Deduction Types | Payroll Officer    |
| Assign / Remove Recurring     | Payroll Officer    |
| Input Variable Deductions     | Payroll Officer    |

#### Related Screens
- Deduction Types List & Form
- Employee Deductions Tab
- Period Variable Deductions Form

#### Acceptance Criteria
✓ Every deduction type shall have a Priority Tier assignment; the system shall not allow saving a deduction type without a Tier.
✓ Deductions shall be applied in strict Tier 1 → 5 sequence.
✓ A deduction entered as a percentage shall compute against the employee's Basic Salary at runtime.

#### Error Conditions
- Missing Priority Tier → "Priority Tier is required."
- Negative deduction amount → "Deduction amount cannot be negative."

#### Dependencies
- Payroll Processing Engine (Module 4.12).
- Loan Management (Module 4.10) — loan installments are a specific subtype of Tier 3/4 deductions.

#### Future Enhancements
- Bulk import of one-off deductions via structured file upload.

---

### 4.07 Statutory Compliance Configuration

#### Purpose
Maintain the configurable statutory rates, brackets, and employer exemptions that govern all mandated tax and contribution calculations.

#### Scope
PAYE tax bracket management, NSSF/pension rate configuration, SDL/WCF rate and exemption settings — all stored with effective dates to support immutable historical calculation.

#### Data Ownership
System Administrator (configuration); Finance Manager (review).

#### Users
System Administrator.

#### Functional Requirements

| ID           | Requirement                        | Description                                                                                                     | Priority |
|--------------|------------------------------------|-----------------------------------------------------------------------------------------------------------------|----------|
| FR-STAT-001  | Manage PAYE Tax Bands              | The system shall maintain a versioned table of PAYE progressive tax brackets (Floor, Ceiling, Fixed Amount, Rate %), each with an effective date range. | High |
| FR-STAT-002  | Manage Contribution Rates          | The system shall allow configuring NSSF (employee and employer portions), SDL rate, and WCF rate as separate, versioned entries with effective dates. | High |
| FR-STAT-003  | View Active Configuration          | The system shall display the currently active statutory configuration for any given date.                       | High     |
| FR-STAT-004  | View Historical Configuration      | The system shall display the statutory configuration that was active during any historical payroll period, for audit purposes. | High |
| FR-STAT-005  | Configure Non-Resident Rate        | The system shall maintain a configurable flat tax rate applied to employees flagged as Non-Resident.            | High     |
| FR-STAT-006  | Configure Secondary Employment Rate| The system shall maintain a configurable flat tax rate applied to employees flagged as having Secondary Employment. | High |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 2: Tanzania Statutory Payroll Rules (full statutory logic).
- `01_BUSINESS_RULES.md` — Section 8: Data Immutability (snapshot requirement).

#### Preconditions
- System Administrator access.

#### Inputs
- Tax band: floor amount, ceiling amount, fixed tax amount, rate percentage, effective start date.
- Contribution rates: scheme name, employee %, employer %, effective date.
- Non-resident rate, secondary employment rate, effective date.

#### Main Workflow
1. Administrator navigates to Settings → Statutory Configuration.
2. Views the currently active PAYE brackets and contribution rates.
3. When a regulatory change occurs, adds a new version of the affected brackets/rates with a new effective start date.
4. Prior configurations are retained and remain queryable.

#### Alternative Flows
- **New Finance Act issued:** Administrator creates a new set of PAYE brackets with an effective date of the new financial year. Old brackets remain for historical period calculations.

#### Outputs
- Versioned statutory configuration.
- Historical configuration available for audit queries.

#### Postconditions
- Payroll engine uses the configuration record whose effective date is ≤ the payroll period end date.

#### Business Events

| Event                             | Trigger                                              |
|-----------------------------------|------------------------------------------------------|
| `statutory.paye_bands.updated`    | A new PAYE bracket set is saved with an effective date.|
| `statutory.contribution.updated`  | A contribution rate (NSSF, SDL, WCF) is updated.     |

#### Permissions

| Action        | Roles                  |
|---------------|------------------------|
| View          | System Admin, Auditor  |
| Create / Edit | System Administrator   |

#### Related Screens
- PAYE Bracket Configuration Screen
- Contribution Rates Screen
- Statutory Configuration History View

#### Acceptance Criteria
✓ The system shall prevent deleting any statutory rate record that was used in a completed payroll run.
✓ A new PAYE bracket set shall not take effect until the specified effective date.
✓ The payroll engine shall always resolve the applicable rate by finding the most recent effective date ≤ period end date.

#### Error Conditions
- Missing effective date on new rate entry → "Effective date is required."
- Overlapping effective date ranges for same bracket set → "A configuration for this effective date already exists."

#### Dependencies
- Payroll Processing Engine (Module 4.12) — rate lookups at calculation time.

#### Future Enhancements
- Full administrative UI for bracket management (MVP relies on seeded defaults and admin-level data entry; a polished management interface is a Phase 2 concern per `docs/specs`).

---

### 4.08 Leave Management

#### Purpose
Record and manage employee leave events and reflect applicable leave impacts on payroll.

#### Scope
Leave type definitions, recording of leave taken per employee per period, and integration of Unpaid Leave into payroll calculation.

#### Data Ownership
HR Manager.

#### Users
HR Manager, HR Officer.

#### Functional Requirements

| ID           | Requirement                   | Description                                                                                              | Priority |
|--------------|-------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-LEAV-001  | Define Leave Types            | The system shall support the following leave types: Annual, Sick, Maternity, Paternity, Unpaid.          | High     |
| FR-LEAV-002  | Record Leave                  | The system shall allow HR to record a leave event for an employee, specifying type, start date, end date, and number of days. | High |
| FR-LEAV-003  | Edit Leave Record             | The system shall allow editing an unprocessed leave record.                                              | High     |
| FR-LEAV-004  | Delete Leave Record           | The system shall allow deleting a leave record before payroll for the relevant period is submitted.      | High     |
| FR-LEAV-005  | Unpaid Leave Payroll Signal   | The system shall pass Unpaid Leave day counts for each employee to the payroll engine for the relevant period. | High |
| FR-LEAV-006  | View Leave Register           | The system shall display a register of all leave records filterable by employee, type, and date range.   | Medium   |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 6: Leave Without Pay (Unpaid Leave) formula.
- `01_BUSINESS_RULES.md` — Section 6: Paid Leaves — do not reduce Basic Salary.
- `docs/specs` — Section 3: Leave-to-Payroll integration is specifically Unpaid Leave for MVP.

#### Preconditions
- Employee must be Active.
- Working Days configuration must be set (Module 4.02 — FR-COMP-005).

#### Inputs
- Employee ID, Leave Type, Start Date, End Date, Number of Days.

#### Main Workflow
1. HR Officer navigates to Leave → Record Leave.
2. Selects employee, leave type, and dates.
3. Saves the record.
4. For Unpaid Leave: the engine automatically deducts the proportional amount from Basic Salary during payroll.
5. For Paid Leave: the record is logged for HR tracking only — no payroll deduction occurs.

#### Alternative Flows
- **Correcting an error:** HR edits the leave record before payroll submission.
- **Leave recorded after payroll lock:** Must be corrected via a supplementary run in the next period.

#### Outputs
- Leave records.
- Unpaid leave data consumed by payroll engine.
- Leave Report.

#### Postconditions
- Unpaid leave is factored into Basic Salary calculation for the affected payroll period.

#### Business Events

| Event                          | Trigger                                               |
|--------------------------------|-------------------------------------------------------|
| `leave.recorded`               | A leave event is saved for an employee.               |
| `leave.updated`                | A leave record is modified.                           |
| `leave.deleted`                | A leave record is removed.                            |

#### Permissions

| Action             | Roles                       |
|--------------------|-----------------------------|
| View               | HR Manager, HR Officer      |
| Record / Edit      | HR Manager, HR Officer      |
| Delete             | HR Manager                  |

#### Related Screens
- Leave Register List
- Leave Entry Form
- Employee Leave History Tab

#### Acceptance Criteria
✓ Recording Unpaid Leave for an employee shall reduce their Basic Salary in the current payroll run using the formula defined in `01_BUSINESS_RULES.md` Section 6.
✓ Recording Annual, Sick, Maternity, or Paternity Leave shall not reduce Basic Salary.
✓ The system shall not permit recording more Unpaid Leave days than the configured working days in the period.
✓ A leave record linked to a Locked or Filed payroll run shall not be editable.

#### Error Conditions
- Unpaid leave days exceed working days in period → "Leave days cannot exceed total working days for this period."

#### Dependencies
- Company Management (Module 4.02) — working days setting.
- Payroll Processing Engine (Module 4.12) — consumes unpaid leave data.

#### Future Enhancements
- Employee-initiated leave requests with manager approval workflow.
- Leave balance accrual and tracking.

---

### 4.09 Attendance & Overtime

#### Purpose
Capture manual overtime and attendance exceptions that are translated into payroll earnings or deductions.

#### Scope
Manual entry of overtime hours or amounts, and recording of unauthorized absence events for the current payroll period.

#### Data Ownership
HR Manager.

#### Users
HR Manager, HR Officer.

#### Functional Requirements

| ID          | Requirement                       | Description                                                                                              | Priority |
|-------------|-----------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-ATT-001  | Input Overtime (Hours)            | The system shall allow HR to record overtime hours worked by an employee in the current period.          | High     |
| FR-ATT-002  | Input Overtime (Fixed Amount)     | The system shall allow HR to record a fixed monetary overtime amount directly, bypassing the hours formula. | High |
| FR-ATT-003  | Edit Overtime Entry               | The system shall allow editing an overtime record before payroll submission.                             | High     |
| FR-ATT-004  | Delete Overtime Entry             | The system shall allow deleting an overtime record before payroll submission.                            | High     |
| FR-ATT-005  | Record Unauthorized Absence       | The system shall allow HR to record unauthorized absence days for an employee, to be treated as Unpaid Leave by the payroll engine. | Medium |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 4: Overtime calculation formula (hours-based and fixed amount).
- `01_BUSINESS_RULES.md` — Section 6: Unauthorized Absences treated as Unpaid Leave.
- `docs/specs` — Section 4: Attendance data entry is manual per employee for MVP.

#### Preconditions
- An open payroll period must exist.
- Employee must be Active.

#### Inputs
- Employee ID, Overtime Hours OR Fixed Monetary Amount.
- Employee ID, Unauthorized Absence Days.

#### Main Workflow
1. HR navigates to Attendance & Overtime.
2. Selects the active period and employee.
3. Enters overtime hours or a fixed amount.
4. Saves the record.
5. During payroll calculation, the engine converts hours to a monetary earning and includes it as a Taxable Earning.

#### Alternative Flows
- **Fixed Amount:** HR enters a TZS amount directly. The hours-based formula is not invoked.

#### Outputs
- Overtime earning line item (taxable).
- Overtime Report.

#### Postconditions
- Overtime is included in the employee's Gross Taxable Salary for the period.

#### Business Events

| Event                          | Trigger                                               |
|--------------------------------|-------------------------------------------------------|
| `overtime.recorded`            | An overtime entry is saved.                           |
| `overtime.updated`             | An overtime entry is modified.                        |
| `overtime.deleted`             | An overtime entry is removed.                         |
| `absence.recorded`             | An unauthorized absence event is recorded.            |

#### Permissions

| Action             | Roles                       |
|--------------------|-----------------------------|
| View               | HR Manager, HR Officer      |
| Create / Edit      | HR Manager, HR Officer      |
| Delete             | HR Manager                  |

#### Related Screens
- Overtime Entry Screen
- Attendance Register

#### Acceptance Criteria
✓ Overtime hours shall produce a monetary earning using the formula defined in `01_BUSINESS_RULES.md` Section 4.
✓ Overtime shall always be treated as a Taxable Earning.
✓ Unauthorized absence days shall be passed to the payroll engine and processed identically to Unpaid Leave.

#### Error Conditions
- Negative hours or amount entered → "Value cannot be negative."

#### Dependencies
- Payroll Period Management (Module 4.11).
- Earnings Management (Module 4.05) — overtime generates an earning record.
- Leave Management (Module 4.08) — unauthorized absence feeds unpaid leave logic.

#### Future Enhancements
- Biometric device integration for automated attendance capture.
- Timesheet submission and manager approval workflow.

---

### 4.10 Loan Management

#### Purpose
Track employee loans and salary advances, automating scheduled installment deductions through payroll.

#### Scope
Loan registration, installment scheduling, balance tracking, and payroll deduction automation.

#### Data Ownership
Finance Manager (approval); HR Manager (registration).

#### Users
HR Manager, Finance Manager, Payroll Officer (View).

#### Functional Requirements

| ID           | Requirement                    | Description                                                                                              | Priority |
|--------------|--------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-LOAN-001  | Register Loan                  | The system shall allow recording a new loan against an employee with Total Amount, Installment Amount, and Start Period. | High |
| FR-LOAN-002  | View Loan Detail               | The system shall display loan summary including Total Amount, Outstanding Balance, and installment history. | High |
| FR-LOAN-003  | Edit Loan Installment          | The system shall allow editing the installment amount on an active loan before the period is submitted.  | High     |
| FR-LOAN-004  | Suspend Loan                   | The system shall allow temporarily suspending a loan deduction for a specific period.                    | Medium   |
| FR-LOAN-005  | Close Loan Early               | The system shall allow marking a loan as fully repaid (manually), recording the closing date.            | High     |
| FR-LOAN-006  | Auto-Deduction                 | The system shall automatically generate the installment deduction record in the payroll engine for every open period where the loan is active and balance > 0. | High |
| FR-LOAN-007  | Balance Update on Lock         | The system shall reduce the loan's outstanding balance by the deducted installment amount when the payroll run is Locked. | High |
| FR-LOAN-008  | Insufficient Funds Flag        | The system shall flag an employee whose net pool cannot cover the scheduled loan installment during the Payroll Preview stage. | High |
| FR-LOAN-009  | Balance Restoration on Reversal| If an approved payroll run is reversed, the system shall restore the loan balance to its pre-deduction value. | High |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 5: Loans & Salary Advances (Tier 3 deduction priority).
- `01_BUSINESS_RULES.md` — Section 1, Stage C: Post-Tax deduction preview blocking logic.
- `01_BUSINESS_RULES.md` — Section 6: Payroll Reversal / Void Runs (balance restoration).
- `docs/specs` — Section 5: Insufficient funds flagged at Preview stage.

#### Preconditions
- Employee must be Active.
- A payroll period must be open for auto-deduction to trigger.

#### Inputs
- Employee ID, Total Loan Amount, Installment Amount, Start Period, Loan Type.

#### Main Workflow
1. HR Manager registers a new loan against the employee.
2. The system creates an installment schedule.
3. When each payroll period opens, the engine automatically includes the installment as a Tier 3 deduction.
4. During Payroll Preview, if the net pool is insufficient, the employee is flagged.
5. HR resolves the flag (adjust installment or acknowledge override).
6. On Payroll Lock, the outstanding balance is decremented.

#### Alternative Flows
- **Balance reaches zero:** Auto-deduction stops automatically; no manual closure required.
- **Payroll reversed:** Loan balance is restored to the pre-deduction value.

#### Outputs
- Loan installment deduction on payslip.
- Loan Report (balances and deductions per period).

#### Postconditions
- Loan balance is accurately reduced upon payroll lock.

#### Business Events

| Event                           | Trigger                                               |
|---------------------------------|-------------------------------------------------------|
| `loan.registered`               | New loan created.                                     |
| `loan.installment.adjusted`     | Installment amount changed.                           |
| `loan.suspended`                | Loan deduction suspended for a period.                |
| `loan.closed`                   | Loan marked as fully repaid.                          |
| `loan.balance.updated`          | Balance decremented on payroll lock.                  |
| `loan.balance.restored`         | Balance restored on payroll reversal.                 |
| `loan.insufficient_funds.flagged`| Employee flagged at preview stage.                   |

#### Permissions

| Action                        | Roles                              |
|-------------------------------|------------------------------------|
| View                          | HR Manager, Finance Manager, Payroll Officer |
| Register / Edit               | HR Manager, Finance Manager        |
| Close / Suspend               | Finance Manager                    |

#### Related Screens
- Loan List (per employee)
- Loan Registration Form
- Loan Detail View (with installment history)

#### Acceptance Criteria
✓ Loan deductions shall automatically cease when the outstanding balance reaches zero.
✓ An employee with an insufficient net pool for a scheduled loan installment shall be flagged during the Preview stage; the run shall not be submittable for that employee until the flag is resolved.
✓ Reversing a locked payroll run shall restore all affected loan balances to their prior values.

#### Error Conditions
- Installment amount exceeds total loan amount → "Installment cannot exceed total loan amount."
- Loan registered against an inactive employee → "Employee must be Active."

#### Dependencies
- Deductions Management (Module 4.06) — loan installments are processed as Tier 3 deductions.
- Payroll Processing Engine (Module 4.12) — preview blocking and balance update on lock.

#### Future Enhancements
- Interest-bearing loans with compound interest schedules.
- Loan approval workflow (HR submits, Finance approves).

---

### 4.11 Payroll Period Management

#### Purpose
Define and govern the discrete time containers within which each payroll run operates.

#### Scope
Period creation, state tracking, period closure, and enforcement of sequential progression rules.

#### Data Ownership
Payroll Officer; System Administrator (for override actions).

#### Users
Payroll Officer, System Administrator.

#### Functional Requirements

| ID           | Requirement                     | Description                                                                                               | Priority |
|--------------|---------------------------------|-----------------------------------------------------------------------------------------------------------|----------|
| FR-PER-001   | Create Payroll Period           | The system shall allow creating a new payroll period specifying period name, start date, and end date.    | High     |
| FR-PER-002   | Enforce Single Open Period      | The system shall prevent creating a new period if another period is currently open.                       | High     |
| FR-PER-003   | Enforce Sequential Progression  | The system shall prevent opening a new period whose start date predates or overlaps with an unclosed prior period. A new period may only be opened when the previous period's standard payroll run has reached **Locked** status. | High |
| FR-PER-004   | View Period List                | The system shall display a list of all payroll periods with their current state.                          | High     |
| FR-PER-005   | Close Period                    | The system shall automatically close a period when its associated payroll run reaches the Locked state.   | High     |
| FR-PER-006   | Reopen Closed Period            | The system shall allow a System Administrator to reopen a Locked period with a mandatory justification recorded to the audit log. | High |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 9.2: Payroll Period Rules (single open period, sequential flow, no future approvals, closed period rules).

#### Preconditions
- Previous period must be Locked or Filed before a new period can be opened.

#### Inputs
- Period name (e.g., "July 2026"), Start Date, End Date.
- Justification note (required for admin reopening).

#### Main Workflow
1. Payroll Officer navigates to Payroll Periods → Create New Period.
2. System verifies the prior period is Locked.
3. Period is created in Draft state.
4. Payroll run proceeds through its lifecycle.
5. On Lock, the period is automatically closed.

#### Alternative Flows
- **Attempted concurrent period:** System blocks creation and displays an error.
- **Admin reopening:** System Administrator provides justification; action is audit-logged.

#### Outputs
- Payroll period record with state.

#### Postconditions
- Period is open (Draft) and the payroll engine can begin a run.

#### Business Events

| Event                         | Trigger                                               |
|-------------------------------|-------------------------------------------------------|
| `period.created`              | New payroll period opened.                            |
| `period.closed`               | Period moves to Locked/Closed state.                  |
| `period.reopened`             | Administrator reopens a closed period.                |

#### Permissions

| Action            | Roles                          |
|-------------------|--------------------------------|
| Create Period     | Payroll Officer                |
| View Periods      | All authenticated users        |
| Reopen Period     | System Administrator           |

#### Related Screens
- Payroll Periods List
- Create Period Form
- Period Detail / Status View

#### Acceptance Criteria
✓ Only one period shall be open at any time.
✓ Attempting to open a period when another is open shall be blocked with a clear message.
✓ Reopening a closed period shall require a mandatory text justification and shall be recorded in the audit log.

#### Error Conditions
- Concurrent period creation attempt → "Cannot open period: [Month/Year] is still open."

#### Dependencies
- Payroll Processing Engine (Module 4.12) — periods are the container for runs.

#### Future Enhancements
- Support for bi-weekly or weekly pay frequencies.

---

### 4.12 Payroll Processing Engine

#### Purpose
Execute the complete payroll calculation pipeline, enforce the maker/checker governance model, and manage the payroll run lifecycle from Draft to Filed.

#### Scope
Pre-flight validation, earnings/deductions aggregation, statutory calculation, net pay determination, run state management, and approval workflow.

#### Data Ownership
Payroll Officer (Maker) for initiation; Finance Manager (Checker) for approval.

#### Users
Payroll Officer (Maker), Finance Manager (Checker).

#### Functional Requirements

| ID           | Requirement                         | Description                                                                                               | Priority |
|--------------|-------------------------------------|-----------------------------------------------------------------------------------------------------------|----------|
| FR-PROC-001  | Load Employees into Run             | The system shall load all Active employees into a new payroll run for the current period.                 | High     |
| FR-PROC-002  | Exclude Terminated Employees        | The system shall automatically exclude employees whose termination date precedes the period start date.   | High     |
| FR-PROC-003  | Include Mid-Period Hires            | The system shall include employees hired during the current period, prorating their Basic Salary accordingly. | High |
| FR-PROC-004  | Include Mid-Period Terminations     | The system shall prorate the Basic Salary of employees terminated during the current period.              | High     |
| FR-PROC-005  | Execute Pre-flight Validation       | The system shall perform a comprehensive validation sweep across all loaded employees and block calculation if any ERROR-level issue is found. | High |
| FR-PROC-006  | Execute Calculation Pipeline        | The system shall calculate gross earnings, pre-tax deductions, PAYE, and net pay for every employee in the run, following the sequence in `01_BUSINESS_RULES.md` Section 1. | High |
| FR-PROC-007  | Detect Insufficient Net Pool        | During the Preview stage, the system shall identify any employee whose net pool cannot fully cover their scheduled post-tax deductions and flag them as errors. | High |
| FR-PROC-008  | Enforce Run State Machine           | The system shall enforce valid state transitions: Draft → Validated → Preview → Approved → Locked → Filed. | High |
| FR-PROC-009  | Enforce Maker/Checker Separation    | The system shall prevent the user who submitted a run for approval from approving that same run.          | High     |
| FR-PROC-010  | Enforce Concurrency Safety          | The system shall ensure that simultaneous state transition attempts by two users result in exactly one success and one clean rejection. | High |
| FR-PROC-011  | Support Checker Rejection           | The system shall allow the Checker to reject an Approved run, reverting it to Draft and recording the rejection reason. | High |
| FR-PROC-012  | Support Payroll Reversal            | The system shall allow a System Administrator to reverse a Locked (but not Filed) payroll run, restoring all affected loan balances and logging the reversal in the audit trail. | High |
| FR-PROC-013  | Support Supplementary Run           | The system shall allow creating an additional off-cycle payroll run for a subset of employees within a period that already has a Locked run. | High |
| FR-PROC-014  | Initiate Amended Return Workflow    | For a Filed run, the system shall support initiating an Amended run linked to the original, rather than allowing silent voiding. | High |
| FR-PROC-015  | Generate Calculation Snapshot       | The system shall store a point-in-time snapshot of the rates, bands, and formulas used for each employee in a run, ensuring historical payslips remain accurate regardless of future configuration changes. | High |
| FR-PROC-016  | Process Retroactive Arrears         | The system shall support the structured arrears workflow defined in `01_BUSINESS_RULES.md` Section 6.    | High     |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 1: Payroll Calculation Order (Stages A, B, C) — authoritative pipeline.
- `01_BUSINESS_RULES.md` — Section 5: Deduction Priority Order — governs post-tax deduction sequence.
- `01_BUSINESS_RULES.md` — Section 6: Edge Cases (mid-period hires, terminations, unpaid leave, arrears, reversals).
- `01_BUSINESS_RULES.md` — Section 7: Rounding Rules.
- `01_BUSINESS_RULES.md` — Section 9.1: Payroll State Machine (all states and transitions, including `Reversed`).
- `01_BUSINESS_RULES.md` — Section 9.2: Payroll Period Rules (Sequential Flow prerequisite is `Locked`).
- `01_BUSINESS_RULES.md` — Section 9.3: Maker / Checker Rule.
- `01_BUSINESS_RULES.md` — Section 9.4: Concurrency Rules.
- `01_BUSINESS_RULES.md` — Section 9.5: Pre-flight Validation Engine (mandatory ERROR checks).

#### Preconditions
- At least one payroll period is in Draft or Validated state.
- All employee records, earnings, deductions, and leave data for the period are entered.

#### Inputs
- Employee records, Basic Salaries, Scheme enrollments.
- Earnings, Deductions, Loan installments for the period.
- Unpaid Leave and Overtime data.
- Active statutory rate configuration tables.

#### Main Workflow
1. Payroll Officer clicks "Validate Run". Pre-flight checks execute.
2. All errors resolved → State advances to Validated.
3. Payroll Officer clicks "Calculate". Engine processes all employees. State → Preview.
4. Payroll Officer reviews payroll register and summary reports.
5. Payroll Officer clicks "Submit for Approval". State → Approved. Checker is notified.
6. Checker logs in, reviews, and clicks "Approve & Lock". State → Locked. Maker is notified.
7. Bank Export and Statutory reports become available.
8. Finance Manager marks the run as "Filed" after government remittance. State → Filed.

#### Alternative Flows
- **Pre-flight fails:** Run stays in Draft. Error list displayed to Maker.
- **Checker rejects:** Run reverts to Draft. Rejection reason recorded. Maker notified. Full recalculation required before resubmission.
- **Calculation error for individual employee:** That employee is marked `FAILED_PROCESSING`. All other employees' results are preserved. HR resolves and re-queues the affected employee via a supplementary run.
- **Insufficient funds for deduction:** Detected at Preview. Run cannot be submitted until all insufficiency flags are resolved.
- **Filed run requires correction:** Amended Return Workflow initiated (FR-PROC-014).

#### Outputs
- Payroll Register.
- Locked payslip records for all employees.
- Employer liability totals (NSSF, SDL, WCF).
- Rounding reconciliation data.

#### Postconditions
- Run is Locked. Payslips are available. Bank export and statutory reports can be generated.

#### Business Events

| Event                                  | Trigger                                                       |
|----------------------------------------|---------------------------------------------------------------|
| `payroll.run.created`                  | Employees loaded into a new run.                              |
| `payroll.run.validated`                | Pre-flight checks pass; run enters Validated state.           |
| `payroll.run.calculated`               | Calculation completes; run enters Preview state.              |
| `payroll.run.submitted`                | Maker submits run; state → Approved.                          |
| `payroll.run.approved`                 | Checker approves run; state → Locked.                         |
| `payroll.run.rejected`                 | Checker rejects run; state → Draft.                           |
| `payroll.run.filed`                    | Run marked as Filed after remittance.                         |
| `payroll.run.reversed`                 | Admin reverses a Locked run.                                  |
| `payroll.employee.failed_processing`   | Individual employee fails calculation; flagged for review.    |
| `payroll.amended_return.initiated`     | Correction workflow started against a Filed run.              |

#### Permissions

| Action                        | Roles                          |
|-------------------------------|--------------------------------|
| Initiate / Validate / Calculate / Submit | Payroll Officer (Maker) |
| Approve / Reject / Mark Filed | Finance Manager (Checker)      |
| Reverse Run                   | System Administrator           |
| Initiate Amended Return       | System Administrator           |

#### Related Screens
- Payroll Run Dashboard
- Pre-flight Validation Report Screen
- Payroll Preview / Register Screen
- Approval Workflow Screen
- Payroll Run History / Audit Trail

#### Acceptance Criteria
✓ Payroll calculations shall follow the sequence defined in `01_BUSINESS_RULES.md` Section 1 exactly.
✓ The Maker shall not be permitted to approve their own submitted run under any circumstances.
✓ The pre-flight validation shall block the run from advancing to Calculated if any ERROR-level issue exists.
✓ All intermediate calculations shall retain high precision internally; the final Net Salary displayed and exported shall be rounded to the nearest whole number as per `01_BUSINESS_RULES.md` Section 7.
✓ The payroll register shall include a Rounding Adjustment column as required by `01_BUSINESS_RULES.md` Section 7.
✓ Two simultaneous Approve actions shall result in exactly one success and one clean error.
✓ A Filed run shall not be silently voidable; the Amended Return Workflow must be used.

#### Error Conditions
- Pre-flight ERROR → Specific error list shown to Maker; run blocked.
- Maker attempts to self-approve → "You cannot approve a run you submitted."
- State transition on stale data (concurrency) → "This run has already been updated by another user. Please refresh."

#### Dependencies
- All employee data modules (4.03–4.10).
- Statutory Compliance Configuration (Module 4.07).
- Payroll Period Management (Module 4.11).
- Payslip Management (Module 4.13) — outputs locked payslips.
- Reports (Module 4.15) — outputs payroll register and statutory reports.
- Notifications (Module 4.16) — notifies Maker and Checker.
- Audit Logs (Module 4.17) — records every state transition.

#### Future Enhancements
- Background chunked processing for organizations exceeding 500 employees.

---

### 4.13 Payslip Management

#### Purpose
Provide employees and HR with a formal, immutable record of compensation for each payroll period.

#### Scope
Payslip generation, display, and export — for employees and administrators.

#### Data Ownership
Payroll Processing Engine (generated); Employee (read access to own).

#### Users
Employee, HR Manager, Payroll Officer, Finance Manager, Auditor.

#### Functional Requirements

| ID           | Requirement                    | Description                                                                                              | Priority |
|--------------|--------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-SLIP-001  | Display Payslip                | The system shall render a formatted payslip for a Locked or Filed payroll run.                           | High     |
| FR-SLIP-002  | Export Payslip to PDF          | The system shall allow exporting a single payslip to a professional PDF document.                        | High     |
| FR-SLIP-003  | View Own Payslips (Employee)   | The system shall allow employees to view and download their own historical payslips for all Locked/Filed runs. | High |
| FR-SLIP-004  | View All Payslips (Admin/HR)   | The system shall allow HR Manager, Finance Manager, and Auditor to view payslips for any employee.       | High     |
| FR-SLIP-005  | Send Payslip via Email         | The system shall send payslip availability notifications to employees when a run is Locked.              | High     |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 8: Data Immutability — payslips must use snapshot data.

#### Preconditions
- Payroll run must be in Locked or Filed state.

#### Inputs
- Locked payroll run snapshot data.

#### Main Workflow
1. Payroll run is Locked.
2. System marks all employee payslips as available.
3. Notification is dispatched to each employee.
4. Employee logs in → navigates to My Payslips → selects period → views or downloads PDF.

#### Alternative Flows
- N/A

#### Outputs
- On-screen payslip view.
- PDF export.

#### Postconditions
- Employee has access to payslip record.

#### Business Events

| Event                     | Trigger                                               |
|---------------------------|-------------------------------------------------------|
| `payslip.available`       | Payroll run is Locked; payslips marked as available.  |

#### Permissions

| Action                    | Roles                                              |
|---------------------------|----------------------------------------------------|
| View own payslip          | Employee                                           |
| View any payslip          | HR Manager, Finance Manager, Payroll Officer, Auditor |
| Export PDF                | All with view access                               |

#### Related Screens
- My Payslips Screen (Employee view)
- Employee Payslip Detail Screen
- Admin Payslip List Screen

#### Acceptance Criteria
✓ Payslip data shall reflect the locked snapshot and shall not change if statutory rates are updated after locking.
✓ An employee shall not be able to view another employee's payslip.
✓ The payslip shall clearly delineate Pre-Tax Deductions and Post-Tax Deductions as separate sections.

#### Error Conditions
- Unauthorized payslip access → Access denied with a generic message.

#### Dependencies
- Payroll Processing Engine (Module 4.12) — payslips are generated on lock.
- Notifications (Module 4.16) — payslip availability emails.

#### Future Enhancements
- Password-protected PDF payslips.
- Bulk email dispatch of all payslips upon run locking.

---

### 4.14 Bank Export

#### Purpose
Generate a structured payment file enabling Finance to upload salary transfers to a commercial bank.

#### Scope
CSV file generation containing all employees' banking details and final net salary for a Locked payroll run.

#### Data Ownership
Finance Manager.

#### Users
Finance Manager.

#### Functional Requirements

| ID           | Requirement                   | Description                                                                                              | Priority |
|--------------|-------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-BANK-001  | Generate Bank Export File     | The system shall generate a CSV file containing each employee's Account Name, Account Number, Bank Code, and Net Amount for a Locked or Filed payroll run. | High |
| FR-BANK-002  | Validate Bank Data Pre-Export | The system shall check that all employees in the run have complete banking information before generating the export and display a list of missing records. | High |
| FR-BANK-003  | Export Download               | The system shall deliver the generated CSV file as a browser download.                                   | High     |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 7: Rounding Rules — net amounts must be whole integers.
- `docs/specs` — Section 2: Target a generic CSV format for MVP.

#### Preconditions
- Payroll run must be in Locked or Filed state.
- All included employees must have bank account details recorded.

#### Inputs
- Locked payroll run data.
- Employee bank details.

#### Main Workflow
1. Finance Manager navigates to Bank Export.
2. Selects the Locked period.
3. System validates banking completeness.
4. Finance Manager downloads the CSV.

#### Alternative Flows
- **Missing bank details:** System displays a list of employees with incomplete records. Export is blocked until corrected.

#### Outputs
- CSV file (Account Name, Account Number, Bank Code, Net Amount).
- Bank Transfer Report (summary).

#### Postconditions
- CSV is downloaded and ready for bank upload.

#### Business Events

| Event                      | Trigger                                               |
|----------------------------|-------------------------------------------------------|
| `bank_export.generated`    | CSV file is generated and downloaded.                 |

#### Permissions

| Action              | Roles              |
|---------------------|--------------------|
| Generate / Download | Finance Manager    |

#### Related Screens
- Bank Export Screen
- Missing Banking Details Warning Screen

#### Acceptance Criteria
✓ The bank export shall only be available for Locked or Filed runs.
✓ Net amounts in the export shall be whole integer TZS values.
✓ The system shall block export generation if any employee in the run is missing bank account details.

#### Error Conditions
- Missing banking details → "Export blocked: [N] employees are missing bank account information. [View list]"

#### Dependencies
- Payroll Processing Engine (Module 4.12) — locked net pay values.
- Employee Management (Module 4.03) — banking details.

#### Future Enhancements
- Formatted exports for specific banks (e.g., NMB, NBC, Absa).
- Direct bank API integration.

---

### 4.15 Reports

#### Purpose
Provide finance, HR, and regulatory stakeholders with structured data outputs for review, reconciliation, and statutory filing.

#### Scope
All standard payroll, HR, and compliance reports generated from locked payroll data.

#### Data Ownership
Finance Manager (financial reports); HR Manager (HR reports); Auditor (read access).

#### Users
HR Manager, Finance Manager, Finance Officer, Auditor.

#### Functional Requirements

| ID          | Report Name                    | Description                                                                                          | Export    | Priority |
|-------------|--------------------------------|------------------------------------------------------------------------------------------------------|-----------|----------|
| FR-REP-001  | Payroll Register               | Full matrix of every employee's Gross, all Earnings, all Deductions, Statutory contributions, and Net Pay for the period. | Excel | High |
| FR-REP-002  | Employee Payroll Summary       | High-level totals per employee: Gross, Tax, Statutory, Net.                                         | PDF/Excel | High     |
| FR-REP-003  | PAYE Report                    | Gross Taxable Salary and PAYE amount per employee, formatted to assist TRA filing.                   | Excel     | High     |
| FR-REP-004  | NSSF Report                    | Total Gross Salary, Employee Contribution (10%), Employer Contribution (10%) per employee.           | Excel     | High     |
| FR-REP-005  | SDL Report                     | Company-level SDL liability calculation for the period.                                              | PDF/Excel | High     |
| FR-REP-006  | WCF Report                     | Company-level WCF liability calculation for the period.                                              | PDF/Excel | High     |
| FR-REP-007  | Deduction Summary              | Aggregated totals of each deduction type (e.g., SACCO total), for third-party remittance.            | Excel     | High     |
| FR-REP-008  | Loan Report                    | Active loans, period installment deducted, and outstanding balance per employee.                     | PDF/Excel | High     |
| FR-REP-009  | Bank Transfer Report           | Summary of net salaries grouped by bank, for bank reconciliation.                                   | PDF       | High     |
| FR-REP-010  | Overtime Report                | Overtime amounts paid per employee for the period.                                                  | Excel     | Medium   |
| FR-REP-011  | Leave Report                   | Leave events per employee, filterable by type and date range.                                        | PDF/Excel | Medium   |
| FR-REP-012  | Staff Turnover Report          | Hires and terminations within a defined date range.                                                  | PDF/Excel | Medium   |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 7: Rounding Adjustment documentation requirement (Payroll Register must include a distinct Rounding Adjustment column).

#### Preconditions
- Payroll run must be in Preview, Approved, Locked, or Filed state for most reports.

#### Inputs
- Period selector.
- Optional filters (Department, Branch, Employee).

#### Main Workflow
1. Finance Manager navigates to Reports.
2. Selects report type and period.
3. Views on-screen data table.
4. Clicks export button to download in desired format.

#### Alternative Flows
- **No data found:** System displays "No records match the selected filters."

#### Outputs
- On-screen data tables.
- PDF and Excel file downloads.

#### Postconditions
- Report is downloaded for use.

#### Business Events

| Event               | Trigger                          |
|---------------------|----------------------------------|
| `report.generated`  | Report is viewed or exported.    |

#### Permissions

| Action              | Roles                                         |
|---------------------|-----------------------------------------------|
| View / Export       | HR Manager, Finance Manager, Finance Officer, Auditor |
| Department-only view| Department Manager (scoped to own dept)       |

#### Related Screens
- Reports Navigation Screen
- Report View Screen (per report type)
- Export/Download Controls

#### Acceptance Criteria
✓ The Payroll Register shall include a distinct Rounding Adjustment column per the requirement in `01_BUSINESS_RULES.md` Section 7.
✓ All report figures shall exactly match the locked payroll run data.
✓ All reports shall support at minimum one downloadable format (PDF or Excel).

#### Error Conditions
- No data for selected period/filter → "No records found for the selected criteria."

#### Dependencies
- Payroll Processing Engine (Module 4.12) — data source.

#### Future Enhancements
- Custom report builder.
- Scheduled report delivery via email.

---

### 4.16 Notifications

#### Purpose
Alert users to required actions and system events in a timely manner.

#### Scope
Email notifications triggered by specific system events.

#### Data Ownership
System (automated).

#### Users
All users (recipients).

#### Functional Requirements

| ID            | Requirement                      | Description                                                                                          | Priority |
|---------------|----------------------------------|------------------------------------------------------------------------------------------------------|----------|
| FR-NOTIF-001  | Password Reset Email             | The system shall send a password reset email when a user requests one.                               | High     |
| FR-NOTIF-002  | Payroll Submitted Notification   | The system shall notify the Checker when a Maker submits a payroll run for approval.                 | High     |
| FR-NOTIF-003  | Payroll Approved Notification    | The system shall notify the Maker when the Checker approves a run.                                   | High     |
| FR-NOTIF-004  | Payroll Rejected Notification    | The system shall notify the Maker, including the rejection reason, when the Checker rejects a run.   | High     |
| FR-NOTIF-005  | Payslip Available Notification   | The system shall notify each employee when their payslip is available following a payroll lock.      | High     |
| FR-NOTIF-006  | Loan Approved Notification       | The system shall notify the employee when a loan is registered against their record.                 | Medium   |

#### Business Rules References
- `00_SPEC.md` — Module 15: Notification requirements.

#### Preconditions
- Recipient must have a valid, active email address on record.
- The notification service must be configured in the system.

#### Inputs
- Event trigger.
- Recipient email address.
- Notification content.

#### Main Workflow
1. A system event fires (e.g., payroll run submitted).
2. System queues a notification to the appropriate recipient.
3. Notification is dispatched asynchronously.
4. Recipient receives the email.

#### Alternative Flows
- **Delivery failure:** System retries notification delivery. Failed attempts are logged for review.

#### Outputs
- Delivered email.

#### Postconditions
- Recipient is informed of the event.

#### Business Events

| Event                           | Trigger                              |
|---------------------------------|--------------------------------------|
| `notification.dispatched`       | Email successfully queued.           |
| `notification.delivery.failed`  | Email delivery attempt failed.       |

#### Permissions
- System (automated dispatch only).

#### Related Screens
- N/A (email only for MVP).

#### Acceptance Criteria
✓ Notifications shall be dispatched without blocking the user interface.
✓ Payroll rejection notifications shall include the Checker's rejection reason.

#### Error Conditions
- Invalid recipient email → Log error; do not abort the triggering operation.

#### Dependencies
- All modules that trigger events (Auth, Payroll Engine, Loan Management).

#### Future Enhancements
- SMS notifications.
- In-app notification bell and unread count.

---

### 4.17 Audit Logs

#### Purpose
Maintain a complete, immutable, and queryable record of all significant system actions for compliance, security, and forensic purposes.

#### Scope
All create, update, delete, and access events across sensitive data — particularly employee records, salary data, statutory configurations, and payroll run state transitions.

#### Data Ownership
System Administrator; Auditor (read access).

#### Users
System Administrator, Auditor.

#### Functional Requirements

| ID           | Requirement                    | Description                                                                                              | Priority |
|--------------|--------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-AUD-001   | Log Data Mutations             | The system shall record an audit entry for every create, update, or delete operation on a sensitive record, capturing the acting user, timestamp, and changed values (before and after). | High |
| FR-AUD-002   | Log Payroll State Transitions  | The system shall record an audit entry for every payroll run state transition, capturing the Maker identity (on submission) and the Checker identity (on approval/rejection). | High |
| FR-AUD-003   | Log Authentication Events      | The system shall record successful logins, failed login attempts, logouts, and password changes.         | High     |
| FR-AUD-004   | Log Admin Overrides            | The system shall record all administrator override actions (e.g., reopening a closed period) with the justification text. | High |
| FR-AUD-005   | View Audit Log                 | The system shall provide a searchable and filterable view of the audit log.                              | High     |
| FR-AUD-006   | Prevent Audit Log Deletion     | The system shall not permit any user to delete audit log records through the application interface.      | High     |

#### Business Rules References
- `01_BUSINESS_RULES.md` — Section 8: Compliance & Audit Rules (audit trail, data immutability, 5-year minimum retention per Income Tax Act, internal 7–10 year policy).
- `01_BUSINESS_RULES.md` — Section 9.3: Maker/Checker identities must be permanently recorded.
- `01_BUSINESS_RULES.md` — Section 10: Audit Retention Policy.

#### Preconditions
- N/A — Audit logging is always active.

#### Inputs
- System events (automated).

#### Main Workflow
- Every significant action in the system automatically generates an audit entry without user intervention.

#### Alternative Flows
- N/A — logging is system-generated.

#### Outputs
- Audit log entries (readable via UI).

#### Postconditions
- Action is recorded and tamper-evident.

#### Business Events
*(The Audit Log is the recording mechanism for all Business Events across all modules.)*

#### Permissions

| Action        | Roles                              |
|---------------|------------------------------------|
| View logs     | System Administrator, Auditor      |
| Delete logs   | No user (system restriction)       |

#### Related Screens
- Audit Log List Screen (filterable by user, date, entity, event type)

#### Acceptance Criteria
✓ Audit records shall include: acting User ID, IP Address, Timestamp, Entity Type, Entity ID, Event Type, Old Value (JSON), New Value (JSON).
✓ No user role, including System Administrator, shall be able to delete an audit log entry via the application.
✓ Payroll run records shall permanently store both the Maker and Checker user IDs.

#### Error Conditions
- N/A — Audit log failures shall be treated as critical system errors and surfaced to the administrator.

#### Dependencies
- All modules — audit events are generated by actions across the entire system.

#### Future Enhancements
- Audit log archiving to cold storage after defined retention periods.

---

### 4.18 System Settings

#### Purpose
Provide a centralized interface for all global configuration that governs system behavior.

#### Scope
Company-level settings not covered in Company Management, notification service configuration, and application toggles.

#### Data Ownership
System Administrator.

#### Users
System Administrator.

#### Functional Requirements

| ID           | Requirement                       | Description                                                                                              | Priority |
|--------------|-----------------------------------|----------------------------------------------------------------------------------------------------------|----------|
| FR-SET-001   | Manage User Accounts              | The system shall provide a list of all user accounts with options to create, edit, deactivate, or reassign roles. | High |
| FR-SET-002   | Configure Notification Service    | The system shall allow configuring the outbound email notification service settings.                     | High     |
| FR-SET-003   | View System Health                | The system shall provide an overview of system status, including any failed notification deliveries.     | Medium   |

#### Preconditions
- System Administrator role required.

#### Permissions

| Action         | Roles                  |
|----------------|------------------------|
| View / Modify  | System Administrator   |

#### Related Screens
- System Settings Screen
- User Management Screen

---

## 5. Cross-Module Workflows

### 5.1 Employee Hire-to-Payslip Workflow

```
HR creates Employee          → Module 4.03 (Employee Management)
         ↓
HR assigns Salary            → Module 4.04 (Salary Structure)
         ↓
HR enters Allowances/Deductions → Modules 4.05 / 4.06
         ↓
HR logs Unpaid Leave / Overtime → Modules 4.08 / 4.09
         ↓
Payroll Officer opens Period → Module 4.11 (Payroll Period)
         ↓
Payroll Officer validates & calculates → Module 4.12 (Engine)
         ↓
Finance Manager approves & locks → Module 4.12 (Engine)
         ↓
Finance Manager downloads Bank Export → Module 4.14
         ↓
Employee views Payslip       → Module 4.13 (Payslip)
         ↓
Finance generates PAYE / NSSF reports → Module 4.15 (Reports)
```

### 5.2 Payroll Correction (Post-Filing) Workflow

```
Filed payroll run identified as erroneous
         ↓
Admin initiates Amended Return Workflow → Module 4.12 (FR-PROC-014)
         ↓
Corrected run calculated and reviewed
         ↓
Checker approves amended run
         ↓
Amended TRA/NSSF export files generated → Module 4.15
         ↓
Amended run marked as Filed
```

### 5.3 Failed Processing Recovery Workflow

```
Payroll Engine flags employee as FAILED_PROCESSING → Module 4.12
         ↓
HR reviews the error (e.g., insufficient net pool for loan) → Module 4.10
         ↓
HR corrects the data constraint (adjusts loan installment)
         ↓
Payroll Officer re-queues the employee in a Supplementary Run → Module 4.12 (FR-PROC-013)
         ↓
Supplementary run calculated, approved, and locked
```

---

## 6. Search & Filtering

| Capability                 | Description                                                                                  | Applies To                       |
|----------------------------|----------------------------------------------------------------------------------------------|----------------------------------|
| Global Employee Search     | Accessible from the application header; searches by Name or Employee Number instantly.       | All authenticated users          |
| Data Table Filtering       | All list views shall support filtering by Branch, Department, Status, and Date Range.        | Employee, Leave, Loan, Report screens |
| Sorting                    | All list columns shall be sortable ascending and descending.                                 | All list views                   |
| Pagination                 | All significant data tables shall paginate results to maintain performance.                  | All list views                   |

> **Note:** Performance-specific requirements (e.g., pagination thresholds, response time targets) are defined in `11_NON_FUNCTIONAL_REQUIREMENTS.md`.

---

## 7. Import & Export

| Direction | Format         | Description                                                                | Module       | MVP |
|-----------|----------------|----------------------------------------------------------------------------|--------------|-----|
| Export    | Excel (.xlsx)  | Reports: Payroll Register, PAYE, NSSF, Deduction Summary                  | 4.15         | ✅   |
| Export    | CSV            | Bank Export, Employee List                                                 | 4.14, 4.03   | ✅   |
| Export    | PDF            | Payslips, HR Reports                                                       | 4.13, 4.15   | ✅   |
| Import    | CSV / Excel    | Bulk variable earnings / deductions import                                 | 4.05, 4.06   | ⏳ Phase 2 |
| Import    | CSV / Excel    | Bulk employee import                                                       | 4.03         | ⏳ Phase 2 |

---

## 8. Business Events Registry

The following table provides a consolidated registry of all business events defined across modules. These events serve as the foundation for notifications, audit logging, and future integrations.

| Module         | Event                                  | Description                                                |
|----------------|----------------------------------------|------------------------------------------------------------|
| Auth           | `user.login.succeeded`                 | User successfully authenticated.                          |
| Auth           | `user.login.failed`                    | Authentication attempt failed.                            |
| Auth           | `user.created`                         | New user account created.                                 |
| Auth           | `user.deactivated`                     | User account deactivated.                                 |
| Auth           | `user.role.assigned`                   | User's role changed.                                      |
| Auth           | `user.password.changed`                | Password updated.                                         |
| Company        | `company.profile.updated`              | Company information changed.                              |
| Company        | `department.created`                   | New department added.                                     |
| Company        | `settings.working_days.changed`        | Global working days setting updated.                      |
| Company        | `public_holiday.created`               | Public holiday added.                                     |
| Employee       | `employee.created`                     | New employee record created.                              |
| Employee       | `employee.activated`                   | Employee set to Active.                                   |
| Employee       | `employee.terminated`                  | Employee marked as Terminated.                            |
| Employee       | `employee.salary.changed`              | Basic Salary amount updated.                              |
| Employee       | `employee.department.changed`          | Department assignment changed.                            |
| Employee       | `employee.scheme.changed`              | Statutory scheme enrollment updated.                      |
| Earnings       | `employee.recurring_earning.added`     | Recurring earning assigned to employee.                   |
| Earnings       | `payroll.variable_earning.added`       | One-off earning added to open period.                     |
| Deductions     | `employee.recurring_deduction.added`   | Recurring deduction assigned.                             |
| Statutory      | `statutory.paye_bands.updated`         | New PAYE bracket set saved.                               |
| Leave          | `leave.recorded`                       | Leave event saved for employee.                           |
| Overtime       | `overtime.recorded`                    | Overtime entry saved.                                     |
| Loan           | `loan.registered`                      | New loan created.                                         |
| Loan           | `loan.balance.updated`                 | Balance decremented on payroll lock.                      |
| Loan           | `loan.insufficient_funds.flagged`      | Insufficient net pool detected at preview.                |
| Period         | `period.created`                       | New payroll period opened.                                |
| Period         | `period.closed`                        | Period closed.                                            |
| Period         | `period.reopened`                      | Admin reopens a closed period.                            |
| Payroll Engine | `payroll.run.submitted`                | Maker submits for approval.                               |
| Payroll Engine | `payroll.run.approved`                 | Checker approves and locks run.                           |
| Payroll Engine | `payroll.run.rejected`                 | Checker rejects run.                                      |
| Payroll Engine | `payroll.run.filed`                    | Run marked as Filed.                                      |
| Payroll Engine | `payroll.run.reversed`                 | Run reversed by Admin.                                    |
| Payroll Engine | `payroll.employee.failed_processing`   | Individual employee flagged for review.                   |
| Payslip        | `payslip.available`                    | Payslips unlocked after run is locked.                    |
| Bank Export    | `bank_export.generated`                | Bank CSV file downloaded.                                 |
| Reports        | `report.generated`                     | Report viewed or exported.                                |

---

## 9. Out of Scope

The following are explicitly outside the MVP boundary and shall not be implemented:

| Feature                            | Status      | Notes                                          |
|------------------------------------|-------------|------------------------------------------------|
| Recruitment / ATS                  | 🔮 Future   |                                                |
| Performance Management / KPIs      | 🔮 Future   |                                                |
| Training & Development             | 🔮 Future   |                                                |
| Asset Management                   | 🔮 Future   |                                                |
| Full General Ledger / Accounting   | 🔮 Future   |                                                |
| Mobile Application                 | 🔮 Future   |                                                |
| Biometric Device Integration       | 🔮 Future   |                                                |
| Direct TRA API Filing              | 🔮 Future   | Manual file upload is the MVP approach         |
| Direct Bank API Integration        | 🔮 Future   | CSV download is the MVP approach               |
| Employee Leave Request Portal      | ⏳ Phase 2  | HR manual entry is MVP                         |
| Bulk Employee Import               | ⏳ Phase 2  |                                                |
| SMS Notifications                  | ⏳ Phase 2  | Email only for MVP                             |
| In-App Notification Bell           | ⏳ Phase 2  |                                                |
| Custom Report Builder              | ⏳ Phase 2  |                                                |
| Interest-Bearing Loan Schedules    | ⏳ Phase 2  |                                                |
