<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed standard roles
        $this->call(RolesSeeder::class);

        // 2. Create a test company
        $company = CompanyProfile::create([
            'company_name' => 'Acme Corporation',
            'registration_number' => 'REG-123456',
            'tin' => '123-456-789',
            'email' => 'admin@acmecorp.test',
            'phone' => '+255 700 000 000',
            'address' => '123 Acme Tower, Dar es Salaam',
        ]);

        // 3. Create a System Administrator user
        $sysAdmin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@payeasy.test',
            'password' => bcrypt('password'),
        ]);
        $sysAdmin->assignRole('system_administrator');

        // 4. Create a Finance Manager user
        $financeManager = User::factory()->create([
            'name' => 'Finance Manager',
            'email' => 'finance@payeasy.test',
            'password' => bcrypt('password'),
        ]);
        $financeManager->assignRole('finance_manager');

        // 5. Create a Payroll Officer user
        $payrollOfficer = User::factory()->create([
            'name' => 'Payroll Officer',
            'email' => 'officer@payeasy.test',
            'password' => bcrypt('password'),
        ]);
        $payrollOfficer->assignRole('payroll_officer');

        // Link users to the company (if using tenant linking logic, or we just rely on passing company UUID in tokens/headers as per API specs)
    }
}
