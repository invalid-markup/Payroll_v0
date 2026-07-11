# 10_UI_UX_SPECIFICATION.md

## 1. Overview

### 1.1 Purpose of this Document
This document serves as the authoritative UI/UX specification for the Tanzanian Payroll & HR Management System MVP. It translates functional, data, and security requirements into concrete user interface guidelines and screen inventories. It explicitly defines the design system, components, and specific screens required to implement the server-rendered Laravel Blade architecture.

### 1.2 Governing Documents
This specification derives strictly from:
* `02_FUNCTIONAL_REQUIREMENTS.md` (Module scope and workflows)
* `03_DATABASE_SPECIFICATION.md` (Data schemas, ENUMs, constraints)
* `05_SYSTEM_ARCHITECTURE.md` (Server-rendered Blade, Tailwind CSS, Alpine.js constraint)
* `06_SECURITY_SPECIFICATION.md` (Role-based access controls)
* `07_API_SPECIFICATION.md` (Target endpoints for UI actions)

### 1.3 Design Goals
* **Compliance-First:** The UI must enforce data immutability and calculation order transparency. Warnings and blocks (e.g., Maker/Checker segregation, Insufficient Funds preview) must be prominently displayed.
* **Clarity over Density:** Avoid overwhelming screens. Use progressive disclosure. Display high-precision data clearly, while adhering to the display rounding rules.
* **Role-Appropriate Density:** Data tables and dashboards must filter to only the columns and metrics relevant to the logged-in user's role (e.g., HR vs. Finance).
* **Responsive Web Design:** Desktop-first for heavy data tables (Payroll Register), but core functions (approvals, dashboard) must be usable on mobile.

---

## 2. Design System & Component Library

### 2.1 Layout & Navigation
* **Application Shell:** Standard sidebar layout with a top navigation bar.
* **Sidebar:** Contains module links filtered by RBAC. Collapsible on smaller screens.
* **Topbar:** Contains the Global Employee Search bar, active payroll period indicator, user profile dropdown, and role indicator.
* **Content Area:** Standardized maximum width container, with a breadcrumb trail for deep navigation.

### 2.2 Color Palette (REVISED)

Colors communicate system state, particularly the payroll lifecycle and security warnings.
All badge styles use Tailwind CSS utility classes.

#### Base Semantic Colors

| Semantic Purpose      | Tailwind Classes                      | Usage                                                              |
|:----------------------|:--------------------------------------|:-------------------------------------------------------------------|
| **Primary Action**    | `bg-blue-600 hover:bg-blue-700 text-white` | Main buttons: Save, Submit, Calculate.                        |
| **Secondary Action**  | `bg-gray-200 hover:bg-gray-300 text-gray-800` | Cancel, Back, secondary form actions.                    |
| **Success**           | `bg-green-100 text-green-800`         | Approved/Locked states, successful toasts.                        |
| **Warning**           | `bg-amber-100 text-amber-800`         | Preview state, insufficient funds, active loan warnings.          |
| **Danger**            | `bg-red-100 text-red-800`             | Rejected, reversed, destructive actions, Maker/Checker blocks.    |
| **Neutral**           | `bg-gray-100 text-gray-700`           | Draft state, disabled fields, read-only data.                     |
| **Info**              | `bg-blue-100 text-blue-800`           | Validated state, informational alerts.                            |
| **Filed / Complete**  | `bg-emerald-100 text-emerald-900`     | Filed and Amended states — distinguishable from Locked green.     |
| **Purple**            | `bg-purple-100 text-purple-800`       | Amended return state.                                              |

### 2.3 Typography & Spacing
* **Font Family:** Inter (or similar clean sans-serif like Roboto).
* **Base Size:** `text-sm` (14px) for data tables to maximize data density; `text-base` (16px) for general text.
* **Currency Formatting:** All final monetary amounts must display as TZS with thousands separators and 0 decimal places (e.g., `TZS 1,250,000`). Internal precision fields (if shown) may display decimals.
* **Spacing:** Tailwind's default spacing scale. Consistent `p-4` or `p-6` for card padding.

### 2.4 Blade Component Library (REVISED — complete list)

All components live in `resources/views/components/`. Developers must not create
inline styling that duplicates these components.

| Component               | Purpose                                                              | Key Props / Variants                                              |
|:------------------------|:---------------------------------------------------------------------|:------------------------------------------------------------------|
| `x-button`              | Standardized button                                                  | `variant`: primary, secondary, danger; `disabled`; `wire:loading` |
| `x-modal`               | Alpine.js confirmation / entry dialog                                | `title`, `x-show`, `confirmText`, `cancelText`                   |
| `x-table`               | Data table wrapper with empty state slot                             | `headers` array, `empty-message`                                  |
| `x-badge`               | ENUM status pill — use values from §2.5 mapping                     | `status` string; auto-maps to correct color class                 |
| `x-form.input`          | Text / email / number / date input with label + error display        | `name`, `label`, `type`, `required`, `readonly`                  |
| `x-form.select`         | Single or multi-select for ENUMs and relationships                   | `name`, `label`, `options`, `multiple`                           |
| `x-form.textarea`       | Multi-line text input                                                | `name`, `label`, `rows`                                          |
| `x-alert`               | Flash / inline alert banner                                          | `type`: success, warning, error, info; `dismissable`             |
| `x-card`                | Standard content card with optional header/footer                    | `title`, `footer` slot                                           |
| `x-stat-card`           | Dashboard KPI widget                                                 | `label`, `value`, `sub-label`, `color`                           |
| `x-page-header`         | Page title row with slot for action buttons                          | `title`, `breadcrumb` slot, `actions` slot                       |
| `x-breadcrumb`          | Hierarchical navigation trail                                        | `items` array of `[label, url]`                                  |
| `x-empty-state`         | Centered empty content placeholder                                   | `title`, `description`, `action` slot                            |
| `x-hard-record-badge`   | Visual indicator that a record is immutable (Hard Record)            | Renders padlock icon + "Locked Record" label; tooltip on hover   |

#### `x-hard-record-badge` Specification
This component is required on every screen that displays a Hard Record
(see `03_DATABASE_SPECIFICATION.md` Principle 2.5).

- **Renders:** `🔒 Locked Record` in `text-xs text-gray-500`
- **Tooltip (title attribute):** "This is a permanent financial record. It cannot be
  edited or deleted. Corrections require a formal amendment workflow."
- **Placement:** Inline next to the page title or record heading.
- **Field treatment:** All form inputs on the same page must render as `<p>` text
  nodes, not `<input>` elements, when the record is a Hard Record.

### 2.5 Complete ENUM Badge Color Mapping

Every ENUM value defined in `03_DATABASE_SPECIFICATION.md` §4.3 must render with the
following badge style. Developers must not invent badge colors outside this table.

#### `payroll_run_status`

| Value       | Badge Style                           | Icon         |
|:------------|:--------------------------------------|:-------------|
| `draft`     | `bg-gray-100 text-gray-700`           | —            |
| `validated` | `bg-blue-100 text-blue-800`           | ✓            |
| `preview`   | `bg-amber-100 text-amber-800`         | 👁           |
| `approved`  | `bg-blue-200 text-blue-900`           | ⏳           |
| `locked`    | `bg-green-100 text-green-800`         | 🔒           |
| `filed`     | `bg-emerald-100 text-emerald-900`     | 📁           |
| `amended`   | `bg-purple-100 text-purple-800`       | ✏️           |
| `reversed`  | `bg-red-100 text-red-800 line-through`| ✕            |

#### `employee_status`

| Value        | Badge Style                    |
|:-------------|:-------------------------------|
| `active`     | `bg-green-100 text-green-800`  |
| `terminated` | `bg-gray-200 text-gray-600`    |

#### `loan_status`

| Value       | Badge Style                    |
|:------------|:-------------------------------|
| `active`    | `bg-blue-100 text-blue-800`    |
| `suspended` | `bg-amber-100 text-amber-800`  |
| `completed` | `bg-green-100 text-green-800`  |
| `closed`    | `bg-gray-200 text-gray-600`    |

#### `processing_status` (per employee row in payroll register)

| Value                       | Badge Style                    | Row Treatment                          |
|:----------------------------|:-------------------------------|:---------------------------------------|
| `pending`                   | `bg-gray-100 text-gray-700`    | Normal row                             |
| `calculated`                | `bg-blue-100 text-blue-800`    | Normal row                             |
| `failed`                    | `bg-red-100 text-red-800`      | Row background `bg-red-50`             |
| `flagged_insufficient_funds`| `bg-amber-100 text-amber-800`  | Row background `bg-amber-50` + ⚠ icon |

#### `payroll_period_status`

| Value    | Badge Style                    |
|:---------|:-------------------------------|
| `open`   | `bg-green-100 text-green-800`  |
| `closed` | `bg-gray-200 text-gray-600`    |

#### `notification_status`

| Value     | Badge Style                    |
|:----------|:-------------------------------|
| `pending` | `bg-gray-100 text-gray-700`    |
| `sent`    | `bg-green-100 text-green-800`  |
| `failed`  | `bg-red-100 text-red-800`      |

### 2.6 Interactivity Rules (Alpine.js vs. Page Reload)
* **Full Page Reloads:** Standard for form submissions (Create/Update), navigation between modules, and pagination.
* **Alpine.js Interactivity:** Used strictly for lightweight, localized state management:
    * Toggling modal visibility.
    * Expanding/collapsing sidebar menus.
    * Showing/hiding dependent form fields (e.g., selecting "Fixed Amount" vs "Hours" for Overtime).
    * Dismissing flash alerts.
    * Simple client-side formatting masks (e.g., currency separators while typing).

---

## 3. Navigation Structure

### 3.1 Sidebar Navigation Structure

The sidebar is always visible on desktop (> 1024px) and hidden behind a hamburger
menu on smaller screens. Nav items are filtered by role — users only see items
their role is permitted to access per `02_FUNCTIONAL_REQUIREMENTS.md` §3.2.

| Section | Nav Item | Route | Roles That See It |
|:--------|:---------|:------|:-----------------|
| **Dashboard** | Dashboard | `/dashboard` | All roles |
| **HR** | Employees | `/employees` | system_administrator, hr_manager, hr_officer, payroll_officer, finance_manager, auditor |
| **HR** | Leave Management | `/leave` | system_administrator, hr_manager, hr_officer |
| **HR** | Attendance & Overtime | `/attendance` | system_administrator, hr_manager, hr_officer |
| **Payroll** | Payroll Periods | `/payroll-periods` | system_administrator, payroll_officer, finance_manager, auditor |
| **Payroll** | Payroll Runs | `/payroll-runs` | system_administrator, payroll_officer, finance_manager, auditor |
| **Payroll** | Earning Types | `/earning-types` | system_administrator, payroll_officer, hr_manager |
| **Payroll** | Deduction Types | `/deduction-types` | system_administrator, payroll_officer |
| **Payroll** | Loans | `/loans` | system_administrator, hr_manager, finance_manager, payroll_officer |
| **Finance** | Bank Export | `/bank-export` | system_administrator, finance_manager |
| **Finance** | Reports | `/reports` | system_administrator, hr_manager, finance_manager, finance_officer, auditor |
| **Compliance** | Statutory Config | `/statutory/configurations` | system_administrator, auditor |
| **Compliance** | PAYE Brackets | `/statutory/paye-brackets` | system_administrator, auditor |
| **Compliance** | Public Holidays | `/public-holidays` | system_administrator |
| **System** | Audit Logs | `/audit-logs` | system_administrator, auditor |
| **System** | Users & Roles | `/users` | system_administrator |
| **System** | Company Profile | `/company` | system_administrator |
| **System** | System Settings | `/settings` | system_administrator |
| **My Account** | My Payslips | `/payslips` | employee |
| **My Account** | My Profile | `/profile` | employee |

### 3.2 Role-Based Navigation Visibility Map

| Nav Section | system_administrator | hr_manager | hr_officer | payroll_officer | finance_manager | finance_officer | department_manager | employee | auditor |
|:------------|:---------:|:----------:|:----------:|:---------------:|:---------------:|:---------------:|:------------:|:--------:|:-------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Employees | ✅ | ✅ | ✅ | ✅ | ✅ | — | ✅* | — | ✅ |
| Leave | ✅ | ✅ | ✅ | — | — | — | — | — | — |
| Attendance | ✅ | ✅ | ✅ | — | — | — | — | — | — |
| Payroll Periods | ✅ | — | — | ✅ | ✅ | — | — | — | ✅ |
| Payroll Runs | ✅ | — | — | ✅ | ✅ | — | — | — | ✅ |
| Earning Types | ✅ | ✅ | — | ✅ | — | — | — | — | — |
| Deduction Types | ✅ | — | — | ✅ | — | — | — | — | — |
| Loans | ✅ | ✅ | — | ✅ | ✅ | — | — | — | — |
| Bank Export | ✅ | — | — | — | ✅ | — | — | — | — |
| Reports | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | — | ✅ |
| Statutory Config | ✅ | — | — | — | — | — | — | — | ✅ |
| PAYE Brackets | ✅ | — | — | — | — | — | — | — | ✅ |
| Public Holidays | ✅ | — | — | — | — | — | — | — | — |
| Audit Logs | ✅ | — | — | — | — | — | — | — | ✅ |
| Users & Roles | ✅ | — | — | — | — | — | — | — | — |
| Company Profile | ✅ | — | — | — | — | — | — | — | — |
| My Payslips | — | — | — | — | — | — | — | ✅ | — |
| My Profile | — | — | — | — | — | — | — | ✅ | — |

*department_manager sees Employees scoped to their department only (via `user_department_scopes` table).

### 3.3 Breadcrumb Pattern

| Screen | Breadcrumb |
|:-------|:-----------|
| Employee List | Home › Employees |
| Employee Detail | Home › Employees › [Employee Name] |
| Salary History | Home › Employees › [Name] › Salary History |
| Payroll Run Detail | Home › Payroll Runs › [Period Name] |
| Pre-flight Results | Home › Payroll Runs › [Period] › Validation |
| Payslip Detail | Home › Payslips › [Month Year] |
| Loan Detail | Home › Loans › [Employee Name] › Loan #[ID] |
| Audit Log | Home › Audit Logs |

### 3.4 Page Title Conventions

| Element | Convention | Example |
|:--------|:-----------|:--------|
| `<title>` tag | `[Screen Name] — Payroll System` | `Employees — Payroll System` |
| `<h1>` tag | Screen name only, with `x-page-header` | `Employees` |
| Record detail `<h1>` | Entity name + identifier | `John Doe — EMP001` |
| Payroll run `<h1>` | Period name + status badge | `July 2026 🔒 LOCKED` |

---

## 4. MVP Screen Inventory & Flows

*Note: All target API endpoints map to the definitions in `07_API_SPECIFICATION.md`.*

### Module 4.01: Authentication & Access
* **Login Screen**
    * **Purpose:** User authentication.
    * **Target API:** `POST /auth/login`
* **Password Reset Screens**
    * **Purpose:** Request link, set new password.
    * **Target API:** `POST /auth/password/email`, `POST /auth/password/reset`

### Module 4.02: Company Management
* **Company Profile Screen**
    * **Purpose:** Manage global company details and configuration.
    * **Data Displayed:** Registration numbers, global "Working Days per Month", active public holidays.
    * **Actions:** Edit Profile, Add Public Holiday.
    * **Target API:** `GET /company`, `PUT /company`
* **Departments & Branches Screen**
    * **Purpose:** Manage organizational structure.
    * **Data Displayed:** List of branches and departments.
    * **Target API:** `GET /departments`, `GET /branches`

### Module 4.03: Employee Management
* **Employee List Screen**
    * **Purpose:** Searchable directory of staff.
    * **Data Displayed:** Name, Employee Number, Department, Status (`Active`, `Terminated`), Role.
    * **Actions:** Filter by branch/department/status, Global Search, "Add Employee".
    * **Target API:** `GET /employees`
* **Employee Detail Screen**
    * **Purpose:** Comprehensive view of a single employee.
    * **Data Displayed:** Tabs for Personal Details, Banking, Salary Structure, Earnings/Deductions, Leaves, Loans.
    * **Target API:** `GET /employees/{id}`
* **Create/Edit Employee Form**
    * **Purpose:** Data entry for personnel. Includes termination flow (triggering active loan warnings).
    * **Target API:** `POST /employees`, `PUT /employees/{id}`

### Module 4.04: Salary Structure
* **Salary Management Panel (Tab on Employee Detail)**
    * **Purpose:** View/Update basic salary and effective dates.
    * **Data Displayed:** Current Basic Salary, History of salary changes.
    * **Actions:** "Add Salary Record" (modal).
    * **Target API:** `POST /employees/{id}/salary`

### Modules 4.05 & 4.06: Earnings & Deductions
* **Earnings / Deductions List (Tab on Employee Detail)**
    * **Purpose:** Manage recurring and one-off additions/subtractions.
    * **Data Displayed:** Type, Amount/Percentage, Taxability, Effective Dates.
    * **Actions:** "Add Earning", "Add Deduction".
    * **Target API:** `GET /employees/{id}/earnings`, `GET /employees/{id}/deductions`

### Module 4.07: Statutory Compliance Configuration
* **Statutory Settings Screen**
    * **Purpose:** Manage tax bands and pension rates.
    * **Data Displayed:** Active PAYE brackets, NSSF rates, SDL/WCF percentages, Effective Dates.
    * **Actions:** "Add New Configuration" (preserves history).
    * **Target API:** `GET /statutory/paye-brackets`, `GET /statutory/configurations`

### Module 4.08 & 4.09: Leave & Attendance
* **Leave & Overtime Register**
    * **Purpose:** List all leave and overtime events for a specific period.
    * **Data Displayed:** Employee, Type, Dates/Hours, Status.
    * **Actions:** "Record Leave" (modal), "Record Overtime" (modal).
    * **Target API:** `POST /leave`, `POST /overtime`

### Module 4.10: Loan Management
* **Loans List Screen**
    * **Purpose:** View active loans across the organization.
    * **Data Displayed:** Employee, Principal, Installment, Remaining Balance, Status.
    * **Target API:** `GET /loans`
* **Loan Detail / Schedule Screen**
    * **Purpose:** View specific loan history and scheduled deductions.
    * **Actions:** "Suspend Installment" (Admin only), "Close Early".
    * **Target API:** `GET /loans/{id}`

### Module 4.11: Payroll Period Management
* **Periods List Screen**
    * **Purpose:** Manage time containers.
    * **Data Displayed:** Month, Year, Start/End Dates, Status (`Open`, `Closed`).
    * **Actions:** "Open New Period" (Blocked if one is already open).
    * **Target API:** `GET /payroll-periods`, `POST /payroll-periods`

### Module 4.12: Payroll Processing Engine (CORE)
* **Payroll Dashboard / Run List**
    * **Purpose:** Overview of active and historical runs.
    * **Data Displayed:** Run ID, Period, Status Badge, Total Gross, Total Net.
    * **Target API:** `GET /payroll-runs`
* **Pre-flight Validation Screen**
    * **Purpose:** Display the results of pre-calculation checks.
    * **Data Displayed:** List of `ERROR` and `WARNING` flags per employee.
    * **Actions:** "Proceed to Calculate" (Disabled if `ERROR` exists), "Refresh Checks".
    * **Target API:** `POST /payroll-runs/{id}/validate`
* **Payroll Preview / Register Screen**
    * **Purpose:** Review calculated data before submission/approval.
    * **Data Displayed:** Wide data table (Employee, Basic, Taxable, PAYE, NSSF, Net, Alerts).
    * **Actions (Maker):** "Submit for Approval".
    * **Actions (Checker):** "Approve & Lock", "Reject Run".
    * **Target API:** `POST /payroll-runs/{id}/calculate` (Maker), `POST /payroll-runs/{id}/approve` (Checker).

### Module 4.13: Payslip Management
* **My Payslips Screen (Employee Portal)**
    * **Purpose:** Employee self-service.
    * **Data Displayed:** List of locked runs with download links.
    * **Target API:** `GET /payslips`
* **Admin Payslip Viewer**
    * **Purpose:** HR/Finance view of generated payslips.
    * **Target API:** `GET /payslips/{id}`

### Module 4.14: Bank Export
* **Bank Export Screen**
    * **Purpose:** Generate CSV for upload to banking portals.
    * **Data Displayed:** Warnings for missing banking details, Total Net to transfer.
    * **Actions:** "Download CSV".
    * **Target API:** `GET /bank-export/download?payroll_period_id=`

### Module 4.15: Reports
* **Reports Dashboard**
    * **Purpose:** Central hub for compliance and financial reporting.
    * **Data Displayed:** List of available reports (PAYE, NSSF, WCF, SDL, General Register).
    * **Actions:** Select Period, Select Format (PDF/Excel), "Generate".
    * **Target API:** `GET /reports/{type}`

### Module 4.17: Audit Logs
* **Audit Trail Screen**
    * **Purpose:** Forensic review of system actions.
    * **Data Displayed:** Timestamp, User, Entity, Event Type, Old/New Value comparison.
    * **Target API:** `GET /audit-logs`

---

## 5. Payroll Engine UX (Detailed)

The payroll engine state machine is the most complex UX flow in the system.
This section defines what each user sees at each state, and how edge cases
are communicated.

### 5.1 State-by-State UI Behavior

#### State: `draft`
**Who sees actions:** Payroll Officer (Maker) only.

| Element | Behavior |
|:--------|:---------|
| Status banner | Grey: "DRAFT — Payroll Run Not Yet Validated" |
| Available buttons | "Validate Run" (primary blue) |
| Employee table | Editable scope — employees can be added/removed |
| Data table columns | Employee # \| Name \| Department \| Basic Salary \| Status |
| Locked indicator | None |

#### State: `validated`
**Who sees actions:** Payroll Officer (Maker) only.

| Element | Behavior |
|:--------|:---------|
| Status banner | Blue: "VALIDATED — Pre-flight Checks Passed" |
| Available buttons | "Calculate Payroll" (primary blue) |
| Pre-flight panel | Collapsible panel showing all WARNING items (yellow rows). If any ERROR existed, run would not have reached this state. |

#### State: `preview`
**Who sees actions:** Payroll Officer (Maker). Checker can view but not act yet.

| Element | Behavior |
|:--------|:---------|
| Status banner | Amber: "PREVIEW — Under Maker Review" |
| Payroll register | Full table: Basic \| Taxable \| PAYE \| NSSF \| Deductions \| Net \| Flags |
| Insufficient funds rows | Row background `bg-amber-50`, ⚠ icon in Flags column, tooltip: "Net pool cannot cover scheduled deductions" |
| Submit button | Disabled (grey) if any `flagged_insufficient_funds` rows exist. Tooltip: "Resolve all flags before submitting." |
| Submit button (no flags) | Enabled primary blue: "Submit for Approval" |
| Resolution path | HR navigates to employee deductions and adjusts. Maker clicks "Recalculate" to refresh. |

#### State: `approved`
**Who sees actions:** Finance Manager (Checker). Maker sees read-only.

| Element | Behavior |
|:--------|:---------|
| Status banner | Blue: "APPROVED — Awaiting Checker Authorization" |
| Maker view | Read-only register. "Submitted by: [Name] on [Date]" label. No action buttons. |
| Checker view — eligible | "Approve & Lock" (green primary) + "Reject Run" (red secondary) |
| Checker view — self-approval blocked | "Approve & Lock" button replaced with red alert: "Action Blocked: You submitted this run. A separate authorized Checker must approve it." Per `06_SECURITY_SPECIFICATION.md` §3.3. |
| Rejection modal | Requires mandatory text: "Rejection Reason (required)". Min 10 characters. Submitting reverts to `draft`. |

#### State: `locked`
**Who sees actions:** Finance Manager. All data read-only.

| Element | Behavior |
|:--------|:---------|
| Status banner | Green: "LOCKED ✓ — Payroll Approved and Finalized" |
| `x-hard-record-badge` | Displayed next to page title |
| All table rows | Read-only. No edit/delete controls. |
| Available buttons | "Download Bank Export CSV" \| "View Reports" \| "Mark as Filed" |
| Payslip availability | Employees are notified by email. Payslips visible in Employee portal. |

#### State: `filed`
**Who sees actions:** Finance Manager (read-only). System Administrator only for amendments.

| Element | Behavior |
|:--------|:---------|
| Status banner | Dark green: "FILED — Statutory Returns Submitted" |
| All data | Read-only. `x-hard-record-badge` on page title. |
| Available buttons | Admin only: "Initiate Amended Return" (subtle secondary button, not primary) |
| Amendment warning | Clicking "Initiate Amended Return" shows modal: "This will create a correction run linked to this filed period. The original filed run cannot be voided. Proceed?" |

#### State: `reversed`
**Who sees actions:** System Administrator only.

| Element | Behavior |
|:--------|:---------|
| Status banner | Red strikethrough: "REVERSED — This run has been voided" |
| All data | Read-only. Retained for audit. |
| Available buttons | None. Terminal state. |
| Reversal info | "Reversed by: [Admin Name] on [Date]. Reason: [justification]" displayed in an info panel. |

---

### 5.2 Amended Return Workflow UX

1. Admin clicks "Initiate Amended Return" on a `filed` run.
2. Confirmation modal (see §5.1 Filed state above).
3. System creates a new linked run with `type: amended_return` in `draft` state.
4. New run screen shows a prominent blue banner: "⚠ AMENDED RETURN — Linked to original
   run #[ID] filed on [Date]. Only employees from the original run may be included."
5. Maker proceeds through the standard `draft → validated → preview → approved → locked → filed`
   workflow on the amended run.
6. Reports generated from an amended run include a header: "AMENDED RETURN — supersedes
   original run #[ID]."

---

### 5.3 Supplementary Run UX

1. Payroll Officer clicks "New Payroll Run" from a period that already has a `locked` standard run.
2. Run type selector shows: Standard (disabled — already locked) \| **Supplementary** (enabled).
3. Supplementary run creation form requires `employee_ids` — a multi-select of employees
   not fully processed in the standard run.
4. Run screen shows blue banner: "SUPPLEMENTARY RUN — Processing [N] employees for period [Month]."
5. Supplementary run goes through the standard state machine independently.
6. Reports and bank export are generated separately for the supplementary run.

---

### 5.4 Concurrency Conflict UX (409 Response)

When two users attempt the same state transition simultaneously and the second request
receives a `409 Conflict` response from the API:

- Full-page error banner (red `x-alert`): "This payroll run was just updated by another
  user. Your action could not be completed. Please refresh the page to see the current state."
- The page automatically offers a "Refresh Now" button.
- No data is lost. The first user's action succeeded; the second is safely rejected.
- This behavior is required by `01_BUSINESS_RULES.md` §9.4 and enforced via
  pessimistic locking per `05_SYSTEM_ARCHITECTURE.md` §4.3.

---

## 6. Responsive Design Rules

### 6.1 Breakpoints (Tailwind defaults)

| Breakpoint | Width        | Layout Changes                                              |
|:-----------|:-------------|:------------------------------------------------------------|
| Mobile     | < 640px (`sm`) | Sidebar hidden, hamburger menu. Single-column forms. Tables scroll horizontally or collapse to card view. |
| Tablet     | 640–1024px (`md`) | Sidebar collapsed to icon-only rail. Two-column forms. Tables show priority columns only. |
| Desktop    | > 1024px (`lg`) | Full sidebar expanded. Multi-column forms. Full data tables. |

### 6.2 Mobile Navigation

- **Trigger:** Hamburger icon (top-left of topbar) visible on `< lg` screens.
- **Behavior:** Clicking opens a full-height slide-out drawer overlay (Alpine.js `x-show`
  with `x-transition`).
- **Drawer contents:** Identical nav items to desktop sidebar, role-filtered identically.
- **Closing:** Tap outside the drawer, or tap the X button at the top of the drawer.
- **Employee role bottom bar:** For the `employee` role on mobile, render a fixed
  bottom navigation bar with three icons: Home \| Payslips \| Profile. This replaces
  the hamburger sidebar for this role only.

### 6.3 Data Table Responsiveness (`x-table`)

Heavy tables (Payroll Register, Employee List, Audit Logs) follow this pattern:

| Screen Size | Behavior |
|:------------|:---------|
| Desktop     | Full table, all columns visible, horizontal scroll only if > 12 columns. |
| Tablet      | Hide lower-priority columns (e.g., Branch, Created At). Core columns remain. |
| Mobile      | Table scrolls horizontally within a fixed container. First column (Name/Employee #) is sticky (frozen). Alternatively, collapse rows to card format for Employee List. |

**Priority columns that must always be visible (never hidden):**
- Employee List: Name, Status, Actions
- Payroll Register: Employee Name, Net Salary, Processing Status / Flags
- Audit Log: Timestamp, User, Event Type

### 6.4 Form Layout on Mobile

- All forms render as **single-column** on mobile (`< md`).
- On tablet and desktop, forms may use a two-column grid (`grid grid-cols-2 gap-6`)
  for fields that are logically paired (e.g., First Name / Last Name, Start Date / End Date).
- Tab navigation on Employee Detail screen collapses to a dropdown select on mobile.

---

## 7. Accessibility & UX Standards

### 7.1 ARIA & Semantic HTML
- All interactive elements (`x-button`, `x-modal`, `x-form.select`) must include
  appropriate ARIA labels: `aria-label`, `aria-describedby`, `role`.
- Modals must set `aria-modal="true"` and trap focus within the modal while open.
- Status badges (`x-badge`) must include `aria-label` describing the status in full
  (e.g., `aria-label="Status: Locked"`) — not just the icon.
- Data tables must use `<th scope="col">` for all column headers.

### 7.2 Focus Management
- On modal open: focus moves to the first focusable element inside the modal.
- On modal close: focus returns to the trigger element that opened it.
- On form validation error: focus moves to the first field with an error.

### 7.3 Color Contrast
- All text/background color combinations must meet **WCAG AA** minimum (4.5:1 ratio
  for normal text, 3:1 for large text).
- Badge text colors in §2.5 are chosen to meet this standard against their backgrounds.
- Do not use color alone to communicate status — always pair with an icon or text label.

### 7.4 Error Announcement
- Server-side `422` validation errors must render in an `x-alert` at the top of the
  form with `role="alert"` so screen readers announce it immediately.
- Individual field errors render below the field as `<p class="text-red-600 text-sm">`.

### 7.5 Loading States
- Any form submission or API call that may take > 300ms must show a loading indicator.
- Primary buttons use `wire:loading` (if using Livewire) or an Alpine.js `loading`
  boolean to swap button text to "Processing…" and disable the button during the request.
- Full-page calculation (payroll engine) shows a progress banner: "Calculating payroll
  for [N] employees… Please do not close this page."

---

## 8. Hard Record & Compliance UX

This section defines the exact UI treatment for every compliance-critical state
in the system. Developers must implement these exactly — they are not stylistic
choices but functional compliance requirements derived from
`03_DATABASE_SPECIFICATION.md` Principles 2.4 and 2.5 and
`06_SECURITY_SPECIFICATION.md` §6.2.

### 8.1 Hard Record Page Treatment

When a user navigates to a page whose primary record is a Hard Record
(e.g., a locked payslip, a filed payroll run, an audit log entry):

| Element | Treatment |
|:--------|:---------|
| Page title | `x-hard-record-badge` rendered inline: `🔒 [Record Name]` |
| All form fields | Rendered as `<p>` or `<span>` text nodes — no `<input>` elements |
| Edit button | Not rendered. Not disabled. Completely absent. |
| Delete button | Not rendered. Not disabled. Completely absent. |
| Page background | Subtle `bg-gray-50` to visually distinguish from editable pages |
| Info notice | Blue `x-alert` at top: "This record is permanently locked. Historical financial records cannot be altered. To make a correction, use the formal amendment workflow." |

### 8.2 Payslip Snapshot Notice
Every payslip view must display this notice below the payslip header:

> 🔒 *"This payslip reflects the statutory rates and salary values that were active
> on [lock date]. It will not change if rates or salaries are updated in the future.
> This is a legally protected financial record."*

### 8.3 Audit Log Page Treatment
- No delete button anywhere on the page, for any role including System Administrator.
- Page header notice: "Audit records are permanent and cannot be modified or deleted.
  Minimum retention: 7 years per `01_BUSINESS_RULES.md` §10."
- `x-hard-record-badge` on page title.
- Rows are read-only text — no action column.

### 8.4 Maker/Checker Self-Approval Block UI
When the authenticated user is the Maker of a run in `approved` state:

- The "Approve & Lock" button is **not rendered** (not just disabled).
- A persistent red `x-alert` replaces it: "Action Blocked: You submitted this
  payroll run and cannot approve it. This is required by segregation-of-duties
  policy (TRA compliance). Another authorized Finance Manager must review."
- The Maker's name and submission timestamp are shown: "Submitted by: [Name],
  [Date and Time]" in a grey info panel.

### 8.5 Salary History — Append-Only Treatment
On the Salary History tab of the Employee Detail screen:

- Salary records are displayed as a chronological timeline (newest first).
- No edit or delete button on any row.
- "Effective From" dates are clearly labelled.
- "Current" badge on the most recent record.
- Notice above the timeline: "Salary history is append-only. To change salary,
  add a new record with the effective date."
- "Add Salary Record" button creates a new entry — it does not modify existing ones.

---

## 9. Notification UX

### 9.1 Toast Notifications (In-App Feedback)

All synchronous actions provide immediate feedback via a toast notification
rendered in the top-right corner of the screen. Toasts auto-dismiss after 4 seconds.

| Trigger | Toast Type | Message |
|:--------|:-----------|:--------|
| Record saved successfully | Success (green) | "[Entity] saved successfully." |
| Validation error (client-side) | Error (red) | "Please correct the errors below." |
| Server error (500) | Error (red) | "An unexpected error occurred. Please try again." |
| Payroll run state changed | Success (green) | "Payroll run status updated to [new state]." |
| Concurrency conflict (409) | Warning (amber) | "This record was updated by another user. Refreshing…" |
| Action blocked (403) | Error (red) | "You do not have permission to perform this action." |

### 9.2 Email Notification Trigger Points

The following user actions trigger email notifications (dispatched asynchronously
per `05_SYSTEM_ARCHITECTURE.md` §6.3 and `02_FUNCTIONAL_REQUIREMENTS.md` §4.16).
The UI must set correct user expectation at each trigger point:

| User Action | Email Sent To | UI Confirmation Text |
|:------------|:--------------|:---------------------|
| Submit run for approval | Finance Manager (Checker) | "Run submitted. The Finance Manager has been notified by email." |
| Approve & Lock run | Payroll Officer (Maker) | "Run locked. The Payroll Officer has been notified." |
| Reject run | Payroll Officer (Maker) | "Run rejected. The Payroll Officer has been notified with your reason." |
| Payroll locked (system event) | All Employees | No UI message — system-triggered on lock. |
| Loan registered | Employee | "Loan registered. The employee has been notified by email." |
| Password reset requested | Requesting user | "If this email exists, a reset link has been sent." |

### 9.3 Employee Payslip Availability Communication
When a run is locked and payslips become available:

- Employees receive an email with a link to log in and view their payslip
  (per FR-NOTIF-005).
- On the Employee dashboard, a green banner appears on next login:
  "Your [Month Year] payslip is now available. [View Payslip →]"
- No in-app notification bell — email only for MVP (per
  `02_FUNCTIONAL_REQUIREMENTS.md` §2, In-App Notification Bell is Phase 2).

---

## 10. Out of Scope (MVP)

The following UI screens and features are explicitly NOT built in the MVP.
They correspond directly to the out-of-scope items in
`02_FUNCTIONAL_REQUIREMENTS.md` §9. Do not design, scaffold, or reference
routes for any of the following:

| Feature | Status | Notes |
|:--------|:-------|:------|
| Employee leave request portal | Phase 2 | HR enters leave manually. Employees cannot submit requests. |
| In-app notification bell / inbox | Phase 2 | Email only for MVP. No bell icon in topbar. |
| SMS notifications | Phase 2 | Email only. |
| Custom report builder UI | Phase 2 | Only predefined reports from FR-REP-001–012. |
| Interest-bearing loan schedules UI | Phase 2 | Standard installment loans only. |
| Recruitment / ATS screens | Future | No candidate or job posting UI. |
| Performance management screens | Future | No KPI or appraisal UI. |
| Training & development screens | Future | Not in system. |
| Asset management screens | Future | Not in system. |
| Mobile native app | Future | Responsive web only. |
| Biometric attendance UI | Future | Manual overtime entry only. |
| Direct TRA / bank API integration UI | Future | File download/upload only. |
| General ledger / journal entry screens | Future | No accounting module. |
| Bulk employee import UI | Phase 2 | Manual entry only for MVP. |
| Multi-company / holding company UI | Future | Single company instance only. |
