<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'payroll_officer',
            'finance_manager',
            'auditor',
        ]);
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'payroll_officer',
            'finance_manager',
            'auditor',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'payroll_officer',
        ]);
    }

    public function update(User $user, PayrollRun $payrollRun): bool
    {
        // Only draft or validated runs can be updated
        if (in_array($payrollRun->status, ['locked', 'filed', 'reversed', 'approved'])) {
            return false;
        }

        return $user->hasAnyRole([
            'system_administrator',
            'payroll_officer',
        ]);
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        // Only draft runs can be deleted
        if ($payrollRun->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole([
            'system_administrator',
            'payroll_officer',
        ]);
    }

    public function submit(User $user, PayrollRun $payrollRun): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'payroll_officer',
        ]);
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        if (! $user->hasAnyRole(['system_administrator', 'finance_manager'])) {
            return false;
        }

        // Maker != Checker Logic
        if ($user->id === $payrollRun->submitted_by_user_id) {
            return false; // A user cannot approve a run they submitted
        }

        return true;
    }
}
