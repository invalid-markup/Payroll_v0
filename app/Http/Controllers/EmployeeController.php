<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\ReactivateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\TerminateEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\Employee\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;

class EmployeeController extends Controller
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * GET /employees
     * 07_API_SPECIFICATION.md §5.1
     */
    public function index()
    {
        $companyId = $this->getCompanyIdFromToken(request());

        $employees = Employee::with(['department', 'branch', 'bankDetails', 'schemeEnrollments'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->paginate(50);

        return EmployeeResource::collection($employees);
    }

    /**
     * GET /employees/{id}
     * 07_API_SPECIFICATION.md §5.2
     */
    public function show(Employee $employee)
    {
        // For employee role, ensure they are viewing themselves
        if (request()->user()->hasRole('employee') && request()->user()->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to employee profile.');
        }

        $employee->load(['department', 'branch', 'bankDetails', 'schemeEnrollments']);

        return new EmployeeResource($employee);
    }

    /**
     * POST /employees
     * 07_API_SPECIFICATION.md §5.3
     */
    public function store(StoreEmployeeRequest $request)
    {
        $companyId = $this->getCompanyIdFromToken($request);

        $employee = $this->employeeService->createEmployee($companyId, $request->validated());

        return (new EmployeeResource($employee))->response()->setStatusCode(201);
    }

    /**
     * PUT /employees/{id}
     * 07_API_SPECIFICATION.md §5.4
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee = $this->employeeService->updateEmployee($employee, $request->validated());

        return new EmployeeResource($employee);
    }

    /**
     * POST /employees/{id}/terminate
     * 07_API_SPECIFICATION.md §5.5
     */
    public function terminate(TerminateEmployeeRequest $request, Employee $employee)
    {
        $employee = $this->employeeService->terminateEmployee(
            $employee,
            $request->input('termination_date'),
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Employee terminated successfully.',
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * POST /employees/{id}/reactivate
     * 07_API_SPECIFICATION.md §5.6
     */
    public function reactivate(ReactivateEmployeeRequest $request, Employee $employee)
    {
        $employee = $this->employeeService->reactivateEmployee(
            $employee,
            $request->input('hire_date')
        );

        return response()->json([
            'message' => 'Employee reactivated successfully.',
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Extract company_id from the token ability (format: company:UUID).
     */
    private function getCompanyIdFromToken($request): ?string
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token) {
            return null;
        }

        foreach ($token->abilities as $ability) {
            if (str_starts_with($ability, 'company:')) {
                return substr($ability, 8);
            }
        }

        return null;
    }
}
