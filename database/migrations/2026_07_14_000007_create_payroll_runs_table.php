<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_period_id')->constrained('payroll_periods')->restrictOnDelete();
            // GAP-COMPANY: company_id has no FK until a companies table is defined.
            $table->uuid('company_id')->nullable();
            $table->enum('type', ['standard', 'supplementary', 'amended_return'])->default('standard');
            // DB spec §4.3: 'amended' is a valid, spec-defined state — must be included.
            $table->enum('status', ['draft', 'validated', 'preview', 'approved', 'locked', 'filed', 'amended', 'reversed'])->default('draft');
            // Maker/Checker identities — permanently bound (DB spec §2.6)
            $table->foreignUuid('submitted_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            // Self-referencing FKs added below after table creation (PG requires PK to exist first).
            $table->uuid('original_run_id')->nullable();
            $table->uuid('reversed_by_run_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Self-referencing FKs must be added after table creation so the PK exists.
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->foreign('original_run_id')->references('id')->on('payroll_runs')->restrictOnDelete();
            $table->foreign('reversed_by_run_id')->references('id')->on('payroll_runs')->restrictOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('
                ALTER TABLE payroll_runs
                ADD CONSTRAINT chk_maker_not_checker
                CHECK (
                    submitted_by_user_id IS NULL
                    OR approved_by_user_id IS NULL
                    OR submitted_by_user_id != approved_by_user_id
                )
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
