<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Requests\Loan\UpdateLoanInstallmentRequest;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Auth::user()->company_id;

        $loans = Loan::whereHas('employee', function ($query) use ($companyId): void {
            $query->where('company_id', $companyId);
        })
            ->with(['employee'])
            ->when($request->filled('status'), fn ($query) => $query->where('loan_status', $request->status))
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->employee_id))
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $statusFilter = $request->get('status', 'all');

        return view('loans.index', compact('loans', 'statusFilter'));
    }

    public function show(string $id): View
    {
        $companyId = Auth::user()->company_id;

        $loan = Loan::whereHas('employee', function ($query) use ($companyId): void {
            $query->where('company_id', $companyId);
        })
            ->with(['employee.department', 'installments.payrollPeriod'])
            ->findOrFail($id);

        return view('loans.show', compact('loan'));
    }

    public function create(): View
    {
        $companyId = Auth::user()->company_id;

        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_number']);

        $periods = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'open')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('loans.create', compact('employees', 'periods'));
    }

    public function edit(string $id): View
    {
        $companyId = Auth::user()->company_id;

        $loan = Loan::whereHas('employee', function ($query) use ($companyId): void {
            $query->where('company_id', $companyId);
        })
            ->with(['employee.department'])
            ->findOrFail($id);

        return view('loans.edit', compact('loan'));
    }

    public function store(StoreLoanRequest $request)
    {
        $validated = $request->validated();

        $activeLoan = Loan::where('employee_id', $validated['employee_id'])
            ->whereIn('loan_status', ['active', 'suspended'])
            ->exists();

        if ($activeLoan) {
            return redirect()->back()->withErrors(['employee_id' => 'Employee already has an active loan.'])->withInput();
        }

        Loan::create([
            'employee_id' => $validated['employee_id'],
            'principal_amount' => $validated['total_amount'],
            'installment_amount' => $validated['installment_amount'],
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        return redirect()->route('loans.index')->with('success', 'Loan created successfully.');
    }

    public function update(UpdateLoanInstallmentRequest $request, string $id)
    {
        $companyId = Auth::user()->company_id;
        $loan = Loan::whereHas('employee', function ($query) use ($companyId): void {
            $query->where('company_id', $companyId);
        })->findOrFail($id);

        $validated = $request->validated();

        $loan->update([
            'installment_amount' => $validated['installment_amount'],
        ]);

        return redirect()->route('loans.show', $loan->id)->with('success', 'Loan installment updated successfully.');
    }
}