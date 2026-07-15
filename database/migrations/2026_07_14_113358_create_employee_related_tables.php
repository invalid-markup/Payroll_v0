<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUuid('bank_id')->constrained('banks')->restrictOnDelete();
            $table->string('branch_code', 50)->nullable();
            $table->string('account_number', 50);
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->timestamps();

            // Partial unique index to enforce one primary account per employee
            // In PostgreSQL, you can use:
            // CREATE UNIQUE INDEX employee_bank_details_is_primary_unique ON employee_bank_details (employee_id) WHERE is_primary = true AND deleted_at IS NULL;
        });

        Schema::create('employee_scheme_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('scheme_code', ['nssf_employer', 'nssf_employee', 'wcf', 'sdl', 'heslb', 'zssf_employer', 'zssf_employee', 'nhif_employer', 'nhif_employee', 'pssfp_employer', 'pssfp_employee', 'workers_union']);
            $table->string('membership_number', 50)->nullable();
            $table->date('effective_from');
            $table->softDeletes(); // Optional, depending if we treat these as hard records after lock. Specs say Hard Record (Effective-Dated) so it should be append-only, but deleted_at is often useful for pre-payroll corrections. The spec says deleted_at IS NULL in the composite key.
            $table->timestamps();

            // Composite unique index
            // CREATE UNIQUE INDEX uq_employee_scheme ON employee_scheme_enrollments (employee_id, scheme_code, membership_number) WHERE deleted_at IS NULL;
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX employee_bank_details_is_primary_unique ON employee_bank_details (employee_id) WHERE is_primary = true AND deleted_at IS NULL;');
            DB::statement('CREATE UNIQUE INDEX uq_employee_scheme ON employee_scheme_enrollments (employee_id, scheme_code, membership_number) WHERE deleted_at IS NULL;');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_scheme_enrollments');
        Schema::dropIfExists('employee_bank_details');
        Schema::dropIfExists('banks');
    }
};
