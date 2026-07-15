<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BankExportController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DeductionTypeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EarningTypeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\LeaveRecordController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PayeBracketController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\StatutoryConfigurationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Public auth routes ─────────────────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

// ─── Authenticated routes (all require valid Sanctum token) ─────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth (post-login)
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/switch-company', [AuthController::class, 'switchCompany']);

    // ── Module 4.02: Company Management ──────────────────────────────────────
    Route::get('/company', [CompanyController::class, 'show']);
    Route::put('/company', [CompanyController::class, 'update']);

    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);

    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);

    Route::get('/cost-centers', [CostCenterController::class, 'index']);

    Route::get('/public-holidays', [PublicHolidayController::class, 'index']);
    Route::post('/public-holidays', [PublicHolidayController::class, 'store']);

    // ── Module 4.03: Employees ────────────────────────────────────────────────
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::post('/employees/{employee}/terminate', [EmployeeController::class, 'terminate']);
    Route::post('/employees/{employee}/reactivate', [EmployeeController::class, 'reactivate']);

    // Employee salary (nested under employees for clarity)
    Route::post('/employees/{employee}/salary', [EmployeeSalaryController::class, 'store']);
    Route::get('/employees/{employee}/salary-history', [EmployeeSalaryController::class, 'index']);

    // ── Module 4.04: Salary Structures ───────────────────────────────────────
    Route::get('/salary-structures', [SalaryStructureController::class, 'index']);
    Route::post('/salary-structures', [SalaryStructureController::class, 'store']);

    // ── Module 4.05: Earning Types ────────────────────────────────────────────
    Route::post('/earning-types', [EarningTypeController::class, 'store']);

    // ── Module 4.06: Deduction Types ──────────────────────────────────────────
    Route::post('/deduction-types', [DeductionTypeController::class, 'store']);

    // ── Module 4.07: Statutory Compliance ────────────────────────────────────
    Route::get('/statutory/configurations', [StatutoryConfigurationController::class, 'index']);
    Route::post('/statutory/configurations', [StatutoryConfigurationController::class, 'store']);
    Route::get('/statutory/paye-brackets', [PayeBracketController::class, 'index']);
    Route::post('/statutory/paye-brackets/bulk', [PayeBracketController::class, 'storeBulk']);

    // ── Module 4.08: Leave Management ────────────────────────────────────────
    Route::get('/leave', [LeaveRecordController::class, 'index']);
    Route::post('/leave', [LeaveRecordController::class, 'store']);
    Route::put('/leave/{leaveRecord}', [LeaveRecordController::class, 'update']);
    Route::delete('/leave/{leaveRecord}', [LeaveRecordController::class, 'destroy']);

    // ── Module 4.09: Attendance & Overtime ───────────────────────────────────
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/overtime', [AttendanceController::class, 'storeOvertime']);
    Route::post('/attendance/absence', [AttendanceController::class, 'storeAbsence']);
    Route::delete('/attendance/{id}', [AttendanceController::class, 'destroy']);

    // ── Module 4.10: Loan Management ─────────────────────────────────────────
    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/loans/{loan}', [LoanController::class, 'show']);
    Route::put('/loans/{loan}/installment', [LoanController::class, 'updateInstallment']);
    Route::post('/loans/{loan}/suspend', [LoanController::class, 'suspend']);
    Route::post('/loans/{loan}/close', [LoanController::class, 'close']);

    // ── Module 4.11: Payroll Periods ──────────────────────────────────────────
    Route::post('/payroll-periods', [PayrollPeriodController::class, 'store']);

    // ── Module 4.12: Payroll Processing Engine ───────────────────────────────
    Route::post('/payroll-runs', [PayrollRunController::class, 'store']);
    Route::post('/payroll-runs/{payrollRun}/validate', [PayrollRunController::class, 'validateRun']);
    Route::post('/payroll-runs/{payrollRun}/calculate', [PayrollRunController::class, 'calculate']);
    Route::get('/payroll-runs/{payrollRun}/flags', [PayrollRunController::class, 'getFlags']);
    Route::post('/payroll-runs/{payrollRun}/submit', [PayrollRunController::class, 'submit']);
    Route::post('/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve']);
    Route::post('/payroll-runs/{payrollRun}/reject', [PayrollRunController::class, 'reject']);
    Route::post('/payroll-runs/{payrollRun}/file', [PayrollRunController::class, 'file']);
    Route::post('/payroll-runs/{payrollRun}/reverse', [PayrollRunController::class, 'reverse']);
    Route::post('/payroll-runs/{payrollRun}/amend', [PayrollRunController::class, 'amend']);

    // ── Module 4.13: Payslips ─────────────────────────────────────────────────
    Route::get('/payslips', [PayslipController::class, 'index']);
    Route::get('/payslips/{id}', [PayslipController::class, 'show']);
    Route::get('/payslips/{id}/export', [PayslipController::class, 'export']);

    // ── Module 4.14: Bank Export ──────────────────────────────────────────────
    Route::get('/bank-export/validate', [BankExportController::class, 'validateData']);
    Route::get('/bank-export/download', [BankExportController::class, 'download']);
});

// Current user info
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
