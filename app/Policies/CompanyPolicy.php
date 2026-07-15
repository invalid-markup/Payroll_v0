<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
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

    public function view(User $user, Company $company): bool
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

    public function create(User $user): bool
    {
        return $user->hasRole('system_administrator');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasRole('system_administrator');
    }
}
