<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanyAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Str::uuid()->toString();

        CompanyProfile::create([
            'company_id' => $companyId,
            'company_name' => 'PayEasy Demo Company',
            'tin' => '100-000-001',
            'registration_number' => 'REG-001',
            'address' => 'Dar es Salaam, Tanzania',
            'phone' => '+255700000001',
            'email' => 'info@payeasydemo.co.tz',
            'working_days_per_month' => 26,
            'financial_year_start_month' => 1,
            'sdl_enabled' => true,
            'wcf_enabled' => true,
            'sdl_employee_threshold' => 4,
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@payeasy.com'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('password'),
                'company_id' => $companyId,
            ]
        );

        $admin->syncRoles(['system_administrator']);

        $this->command->info("Company created with company_id: {$companyId}");
        $this->command->info('Admin user linked: admin@payeasy.com');
    }
}
