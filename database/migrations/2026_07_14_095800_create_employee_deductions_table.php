<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('deduction_type_id');
            $table->uuid('payroll_period_id')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->decimal('percentage', 8, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('deduction_type_id')->references('id')->on('deduction_types')->restrictOnDelete();
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
