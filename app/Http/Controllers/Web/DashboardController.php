<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunResult;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $companyId = $user->company_id;

        $activePeriod = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->orderBy('start_date', 'desc')
            ->first();

        $recentRuns = PayrollRun::where('company_id', $companyId)
            ->with(['payrollPeriod', 'processedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $stats = [
            'active_employees' => Employee::where('company_id', $companyId)
                ->where('status', 'active')
                ->count(),

            'gross_this_period' => $activePeriod
                ? PayrollRunResult::whereHas('payrollRun', fn ($q) => $q->where('payroll_period_id', $activePeriod->id))
                    ->sum('gross_salary_amount')
                : 0,

            'active_loans' => Loan::whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
                ->where('loan_status', 'active')
                ->count(),

            'total_loan_balance' => Loan::whereHas('employee', fn ($q) => $q->where('company_id', $companyId))
                ->where('loan_status', 'active')
                ->sum(DB::raw('principal_amount - total_repaid_amount')),

            'runs_ytd' => PayrollRun::where('company_id', $companyId)
                ->whereYear('created_at', now()->year)
                ->count(),

            'locked_runs_ytd' => PayrollRun::where('company_id', $companyId)
                ->whereYear('created_at', now()->year)
                ->whereIn('status', ['locked', 'filed'])
                ->count(),

            'pending_approvals' => PayrollRun::where('company_id', $companyId)
                ->where('status', 'draft')
                ->count(),

            'insufficient_fund_flags' => 0,
        ];

        return view('dashboard', compact('activePeriod', 'recentRuns', 'stats'));
    }
}
