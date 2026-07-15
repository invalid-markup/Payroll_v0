<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employeeService)
    {
    }

    public function index(Request $request): View
    {
        $companyId = Auth::user()->company_id;

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        $employees = Employee::where('company_id', $companyId)
            ->with(['department', 'currentSalary'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where(function ($inner) use ($request): void {
                    $search = '%'.$request->search.'%';
                    $inner->whereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", [$search])
                        ->orWhere('employee_number', 'ILIKE', $search)
                        ->orWhere('tin', 'ILIKE', $search);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                if ($request->status === 'active') {
                    $query->where('status', 'ACTIVE');

                    return;
                }

                $query->where('status', '!=', 'ACTIVE');
            })
            ->when($request->filled('department_id'), function ($query) use ($request): void {
                $query->where('department_id', $request->department_id);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25);

        return view('employees.index', compact('employees', 'departments'));
    }

    public function show(string $id): View
    {
        $companyId = Auth::user()->company_id;

        $employee = Employee::where('company_id', $companyId)
            ->with([
                'branch',
                'department.branch',
                'bankDetails.bank',
                'currentSalary',
                'salaryHistories' => fn ($query) => $query->orderByDesc('effective_from'),
                'earnings.earningType',
                'deductions.deductionType',
                'leaveRecords' => fn ($query) => $query->orderByDesc('start_date'),
                'loans.installments',
                'overtimeRecords' => fn ($query) => $query->orderByDesc('created_at'),
            ])
            ->findOrFail($id);

        return view('employees.show', compact('employee'));
    }

    public function create(): View
    {
        $departments = Department::query()->orderBy('name')->get();
        $branches = Branch::query()->orderBy('name')->get();
        $statuses = ['ACTIVE', 'ON_LEAVE', 'SUSPENDED', 'TERMINATED', 'DISMISSED', 'DECEASED'];

        return view('employees.create', compact('departments', 'branches', 'statuses'));
    }

    public function edit(string $id): View
    {
        $companyId = Auth::user()->company_id;

        $employee = Employee::where('company_id', $companyId)->findOrFail($id);
        $departments = Department::query()->orderBy('name')->get();
        $branches = Branch::query()->orderBy('name')->get();
        $statuses = ['ACTIVE', 'ON_LEAVE', 'SUSPENDED', 'TERMINATED', 'DISMISSED', 'DECEASED'];

        return view('employees.edit', compact('employee', 'departments', 'branches', 'statuses'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $companyId = Auth::user()->company_id;

        $this->employeeService->createEmployee($companyId, $request->validated());

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function update(UpdateEmployeeRequest $request, string $id)
    {
        $employee = Employee::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->employeeService->updateEmployee($employee, $request->validated());

        return redirect()->route('employees.show', $employee->id)->with('success', 'Employee updated successfully.');
    }
}