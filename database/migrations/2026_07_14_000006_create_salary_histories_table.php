<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            // DB spec §5.4: column is basic_salary_amount, not salary
            $table->decimal('basic_salary_amount', 15, 4)->default(0);
            $table->uuid('salary_structure_id')->nullable();
            $table->date('effective_from');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            // DB spec §11.3: mandatory composite index for effective-dating resolution
            $table->index(['employee_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_histories');
    }
};
