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
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('payroll_period_id');
            $table->decimal('amount_deducted', 15, 4);
            $table->decimal('outstanding_balance_before', 15, 4);
            $table->decimal('outstanding_balance_after', 15, 4);
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->restrictOnDelete();
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
