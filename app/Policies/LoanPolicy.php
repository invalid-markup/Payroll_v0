<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanPolicy
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
            // Employee can't viewAny broadly, handled via own scope.
        });
    }

    public function view(User $user, Loan $loan): bool
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
            return $user->employee_id === $loan->employee_id;
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

    public function update(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
        ]);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return false; // Loans are suspended/closed, not deleted
    }

    public function suspend(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
        ]);
    }

    public function close(User $user, Loan $loan): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'hr_manager',
            'hr_officer',
        ]);
    }
}
