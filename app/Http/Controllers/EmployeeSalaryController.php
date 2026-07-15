<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeSalaryRequest;
use App\Http\Resources\Employee\SalaryHistoryResource;
use App\Models\Employee;
use App\Models\SalaryHistory;
use Illuminate\Support\Str;

class EmployeeSalaryController extends Controller
{
    /**
     * POST /employees/{employee}/salary
     * Creates an append-only salary history record — existing records are NEVER modified.
     * DB Spec §2.3 (Append-Only Salary History); FR-SAL-003.
     * 07_API_SPECIFICATION.md §6.3
     */
    public function store(StoreEmployeeSalaryRequest $request, Employee $employee)
    {
        // DB Spec §2.3: salary changes are always a new row — never an UPDATE.
        $history = SalaryHistory::create([
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'basic_salary_amount' => $request->input('basic_salary_amount'),
            'salary_structure_id' => $request->input('salary_structure_id'),
            'effective_from' => $request->input('effective_from'),
        ]);

        $history->load('salaryStructure');

        return (new SalaryHistoryResource($history))->response()->setStatusCode(201);
    }

    /**
     * GET /employees/{employee}/salary-history
     * Returns all salary history records in descending chronological order.
     * 07_API_SPECIFICATION.md §6.4
     */
    public function index(Employee $employee)
    {
        $history = $employee->salaryHistories()
            ->with('salaryStructure')
            ->orderByDesc('effective_from')
            ->get();

        return SalaryHistoryResource::collection($history);
    }
}
