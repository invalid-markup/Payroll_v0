<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_result_id');
            // DB spec §5.11: type (ENUM line_item_type) and name (denormalized copy from config)
            $table->enum('type', ['earning', 'deduction', 'tax', 'employer_contribution']); // line_item_type
            $table->string('code')->nullable();     // Denormalized code from earning/deduction type
            $table->string('name')->nullable();     // Denormalized name from earning/deduction type (DB spec §8.1)
            $table->decimal('amount', 15, 4)->default(0);
            $table->json('meta')->nullable();       // Optional structured data for audit
            $table->timestamps();

            $table->foreign('payroll_run_result_id')->references('id')->on('payroll_run_results')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_line_items');
    }
};
