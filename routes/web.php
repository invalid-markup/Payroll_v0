<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\LoanController;
use App\Http\Controllers\Web\PayrollPeriodController;
use App\Http\Controllers\Web\PayrollRunController;
use App\Models\AuditLog;
use App\Models\CompanyProfile;
use App\Models\PayrollRun;
use App\Models\PublicHoliday;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
    });

    Route::prefix('payroll-periods')->name('payroll-periods.')->group(function () {
        Route::get('/', [PayrollPeriodController::class, 'index'])->name('index');
        Route::get('/{id}', [PayrollPeriodController::class, 'show'])->name('show');
    });

    Route::prefix('payroll-runs')->name('payroll-runs.')->group(function () {
        Route::get('/', [PayrollRunController::class, 'index'])->name('index');
        Route::get('/create', [PayrollRunController::class, 'create'])->name('create');
        Route::post('/', [PayrollRunController::class, 'store'])->name('store');
        Route::get('/{id}', [PayrollRunController::class, 'show'])->name('show');
        Route::post('/{id}/calculate', [PayrollRunController::class, 'calculate'])->name('calculate');
        Route::post('/{id}/submit', [PayrollRunController::class, 'submit'])->name('submit');
        Route::post('/{id}/approve', [PayrollRunController::class, 'approve'])->name('approve');
        Route::post('/{id}/file', [PayrollRunController::class, 'file'])->name('file');
        Route::post('/{id}/amend', [PayrollRunController::class, 'amend'])->name('amend');
    });

    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::post('/', [LoanController::class, 'store'])->name('store');
        Route::get('/create', [LoanController::class, 'create'])->name('create');
        Route::get('/{id}', [LoanController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [LoanController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LoanController::class, 'update'])->name('update');
    });

    Route::get('/profile', function () {
        return view('profile.show', [
            'user' => Auth::user(),
        ]);
    })->name('profile.show');

    Route::get('/payslips', function () {
        $companyId = Auth::user()->company_id;

        $runs = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['locked', 'filed'])
            ->with(['payrollPeriod', 'processedBy'])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return view('payslips.index', [
            'runs' => $runs,
        ]);
    })->name('payslips.index');

    Route::middleware('role:system_administrator')->group(function () {
        Route::get('/company', function () {
            $companyId = Auth::user()->company_id;

            return view('company.show', [
                'profile' => CompanyProfile::where('company_id', $companyId)->first(),
                'holidays' => PublicHoliday::where('company_id', $companyId)->orderBy('date', 'desc')->get(),
            ]);
        })->name('company.show');

        Route::get('/audit-logs', function () {
            $companyId = Auth::user()->company_id;

            $logs = AuditLog::with('user')
                ->whereHas('user', fn ($query) => $query->where('company_id', $companyId))
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            return view('audit.index', [
                'logs' => $logs,
            ]);
        })->name('audit.index');
    });

    Route::middleware('role:system_administrator|finance_manager|auditor')->group(function () {
        Route::get('/reports', fn () => view('reports.index'))->name('reports.index');
    });

    Route::middleware('role:system_administrator|finance_manager')->group(function () {
        Route::get('/bank-export', fn () => view('bank-export.index'))->name('bank-export.index');
    });
});
