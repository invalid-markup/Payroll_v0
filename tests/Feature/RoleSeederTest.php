<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_exactly_nine_roles()
    {
        $this->seed(RoleSeeder::class);

        $expectedRoles = [
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

        $this->assertCount(9, Role::all());

        foreach ($expectedRoles as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
