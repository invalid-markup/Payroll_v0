<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_id');
            $table->uuid('generated_by_user_id');
            $table->string('file_hash', 64); // SHA-256 = 64 hex chars
            $table->unsignedInteger('total_records');
            $table->decimal('total_amount', 15, 4);
            $table->timestamps();

            $table->foreign('payroll_run_id')
                ->references('id')
                ->on('payroll_runs')
                ->restrictOnDelete();

            $table->foreign('generated_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_exports');
    }
};
