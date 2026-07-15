<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed standard roles
        $this->call(RolesSeeder::class);

        // 2. Create demo company + admin user linked to it
        $this->call(CompanyAndAdminSeeder::class);
    }
}
