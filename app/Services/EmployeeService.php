<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeService
{
    /**
     * Create an employee along with their bank details and scheme enrollments.
     */
    public function createEmployee(string $companyId, array $data): Employee
    {
        return DB::transaction(function () use ($companyId, $data) {
            $employee = Employee::create([
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'employee_number' => $data['employee_number'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'job_title' => $data['job_title'] ?? null,
                'status' => $data['status'],
                'employment_type' => $data['employment_type'],
                'resident_status' => $data['resident_status'],
                'secondary_employment_flag' => $data['secondary_employment_flag'] ?? false,
                'hire_date' => $data['hire_date'] ?? null,
                'department_id' => $data['department_id'],
                'branch_id' => $data['branch_id'],
                'tin' => $data['tin'] ?? null,
                'nssf_number' => $data['nssf_number'] ?? null,
            ]);

            if (! empty($data['bank_details'])) {
                // Ensure only one is primary. If multiple, make the first one primary.
                // If only one, it should be primary.
                $hasPrimary = collect($data['bank_details'])->contains('is_primary', true);

                foreach ($data['bank_details'] as $index => $bankData) {
                    $isPrimary = $bankData['is_primary'] ?? false;
                    if (! $hasPrimary && $index === 0) {
                        $isPrimary = true;
                    }

                    $employee->bankDetails()->create([
                        'id' => Str::uuid(),
                        'bank_id' => $bankData['bank_id'],
                        'branch_code' => $bankData['branch_code'] ?? null,
                        'account_number' => $bankData['account_number'],
                        'is_primary' => $isPrimary,
                    ]);
                }
            }

            if (! empty($data['scheme_enrollments'])) {
                foreach ($data['scheme_enrollments'] as $schemeData) {
                    $employee->schemeEnrollments()->create([
                        'id' => Str::uuid(),
                        'scheme_code' => $schemeData['scheme_code'],
                        'membership_number' => $schemeData['membership_number'] ?? null,
                        'effective_from' => $schemeData['effective_from'],
                    ]);
                }
            }

            return $employee->load(['department', 'branch', 'bankDetails', 'schemeEnrollments']);
        });
    }

    /**
     * Update an employee's core details.
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update([
            'employee_number' => $data['employee_number'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'job_title' => $data['job_title'] ?? null,
            'status' => $data['status'],
            'employment_type' => $data['employment_type'],
            'resident_status' => $data['resident_status'],
            'secondary_employment_flag' => $data['secondary_employment_flag'] ?? false,
            'hire_date' => $data['hire_date'] ?? null,
            'department_id' => $data['department_id'],
            'branch_id' => $data['branch_id'],
            'tin' => $data['tin'] ?? null,
            'nssf_number' => $data['nssf_number'] ?? null,
        ]);

        // Note: Bank details and scheme enrollments typically have their own dedicated endpoints
        // or effective-dated update logic, so they are not updated here in place.

        return $employee->fresh(['department', 'branch', 'bankDetails', 'schemeEnrollments']);
    }

    /**
     * Terminate an employee.
     */
    public function terminateEmployee(Employee $employee, string $terminationDate, string $reason): Employee
    {
        $employee->update([
            'status' => 'terminated',
            'termination_date' => $terminationDate,
        ]);

        // Future: Log reason to audit or employment_history table if required.

        return $employee->fresh();
    }

    /**
     * Reactivate an employee.
     */
    public function reactivateEmployee(Employee $employee, string $hireDate): Employee
    {
        $employee->update([
            'status' => 'active',
            'hire_date' => $hireDate,
            'termination_date' => null,
        ]);

        return $employee->fresh();
    }
}
