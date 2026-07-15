<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'system_administrator',
            'hr_manager',
            'hr_officer',
            'payroll_officer',
            'finance_manager',
            'finance_officer',
            'department_manager',
            'employee',
            'auditor',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
