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
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('payroll_period_id');
            $table->uuid('approved_by_user_id')->nullable();
            $table->string('overtime_type'); // hours_based or fixed_amount
            $table->decimal('hours', 8, 4)->nullable();
            $table->decimal('overtime_rate_multiplier', 8, 4)->nullable();
            $table->decimal('fixed_amount', 15, 4)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->restrictOnDelete();
            $table->foreign('approved_by_user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
