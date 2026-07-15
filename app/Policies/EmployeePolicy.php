<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return tap($user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
            'payroll_officer',
            'finance_manager',
            'finance_officer',
            'auditor',
        ]), function (bool $allowed) {
            // Further scoped in Controller/Repository for Dept Manager
        });
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
            'payroll_officer',
            'finance_manager',
            'finance_officer',
            'auditor',
        ])) {
            return true;
        }

        if ($user->hasRole('employee')) {
            return $user->employee_id === $employee->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
        ]);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
        ]);
    }
}
