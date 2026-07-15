<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_id');
            $table->uuid('employee_id');
            // DB spec §5.11: processing_status tracks per-employee outcome
            $table->enum('processing_status', ['success', 'failed', 'skipped'])->default('success');
            // DB spec §5.11: spec-exact column names for all monetary fields
            $table->decimal('basic_salary_amount', 15, 4)->default(0);
            $table->decimal('gross_salary_amount', 15, 4)->default(0);
            $table->decimal('taxable_income_amount', 15, 4)->default(0);
            $table->decimal('nssf_deduction_amount', 15, 4)->default(0);
            $table->decimal('paye_tax_amount', 15, 4)->default(0);
            $table->decimal('total_deductions_amount', 15, 4)->default(0);
            $table->decimal('net_salary_amount', 15, 4)->default(0);
            // DB spec §2.7 & BR §7: rounding residual stored as separate column
            $table->decimal('rounding_adjustment', 15, 4)->default(0);
            // DB spec §8.2: calculation_snapshot JSON column (not just 'snapshot')
            $table->json('calculation_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->restrictOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->index(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_results');
    }
};
