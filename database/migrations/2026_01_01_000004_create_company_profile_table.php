<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DB spec §5.2: Single-row table; enforced via CHECK (id = 1) or app-layer guard
        Schema::create('company_profile', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
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
