<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // GAP-COMPANY: company_id has no FK until a companies table is defined.
            // Scoping to company_id is enforced at the application layer.
            $table->uuid('company_id')->nullable();
            $table->string('employee_number', 50)->unique(); // uq_employees_employee_number
            $table->string('first_name');
            $table->string('last_name');
            $table->string('job_title')->nullable();
            $table->enum('status', ['active', 'terminated'])->default('active');
            $table->enum('employment_type', ['permanent', 'contract', 'casual'])->default('permanent');
            $table->enum('resident_status', ['resident', 'non_resident'])->default('resident');
            // DB spec §5.3: secondary_employment_flag for flat 30% PAYE. BR §2.
            $table->boolean('secondary_employment_flag')->default(false)->comment('BR §2: Secondary employment taxed at flat 30%');
            // DB spec §5.3: hire_date and termination_date for proration. BR §6.
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->string('tin', 50)->nullable();    // uq_employees_tin enforced at app layer
            $table->string('nssf_number', 50)->nullable(); // uq_employees_nssf_number enforced at app layer
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
