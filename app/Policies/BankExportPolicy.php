<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankExportPolicy
{
    use HandlesAuthorization;

    public function download(User $user): bool
    {
        return $user->hasAnyRole([
            'system_administrator',
            'finance_manager',
        ]);
    }
}
