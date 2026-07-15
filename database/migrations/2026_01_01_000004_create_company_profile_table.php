<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DB spec §5.2: company profile config. company_id links to future companies table.
        Schema::create('company_profile', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // GAP-COMPANY: company_id links to a companies table (future phase)
            $table->uuid('company_id')->nullable();
            $table->string('company_name');        // renamed from 'name' for clarity
            $table->string('tin', 50)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->integer('working_days_per_month')->default(26);
            $table->integer('financial_year_start_month')->default(1); // 1 = January
            $table->boolean('sdl_enabled')->default(true);
            $table->boolean('wcf_enabled')->default(true);
            $table->integer('sdl_employee_threshold')->default(4); // SDL applies if >= this headcount
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profile');
    }
};
