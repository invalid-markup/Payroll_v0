<?php

namespace App\Policies;

use App\Models\PayrollRunResult;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollRunResultPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
            'payroll_officer',
            'finance_manager',
            'finance_officer',
            'auditor',
        ]);
    }

    public function view(User $user, PayrollRunResult $payslip): bool
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
            return $user->employee_id === $payslip->employee_id;
        }

        return false;
    }
}
