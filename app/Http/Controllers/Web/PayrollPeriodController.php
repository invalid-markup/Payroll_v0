<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function index(): View
    {
        $companyId = Auth::user()->company_id;

        $periods = PayrollPeriod::where('company_id', $companyId)
            ->withCount('payrollRuns')
            ->orderBy('start_date', 'desc')
            ->paginate(20);

        return view('payroll-periods.index', compact('periods'));
    }

    public function show(string $id): View
    {
        $companyId = Auth::user()->company_id;

        $period = PayrollPeriod::where('company_id', $companyId)
            ->with('payrollRuns.processedBy')
            ->findOrFail($id);

        return view('payroll-periods.show', compact('period'));
    }
}
