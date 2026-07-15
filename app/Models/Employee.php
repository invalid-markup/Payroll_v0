<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'company_id',
        'employee_number',
        'first_name',
        'last_name',
        'status',
        'department_id',
        'branch_id',
        'employment_type',
        'resident_status',
        'tin',
        'nssf_number',
        'job_title',
        'secondary_employment_flag',
        'hire_date',
        'termination_date',
    ];

    protected $casts = [
        'company_id' => 'string',
        'department_id' => 'string',
        'branch_id' => 'string',
        'secondary_employment_flag' => 'boolean',
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bankDetails()
    {
        return $this->hasMany(EmployeeBankDetail::class);
    }

    public function schemeEnrollments()
    {
        return $this->hasMany(EmployeeSchemeEnrollment::class);
    }

    public function salaryHistories()
    {
        return $this->hasMany(SalaryHistory::class);
    }

    public function payrollRunResults()
    {
        return $this->hasMany(PayrollRunResult::class);
    }

    public function earnings()
    {
        return $this->hasMany(EmployeeEarning::class);
    }

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function leaveRecords()
    {
        return $this->hasMany(LeaveRecord::class);
    }

    public function overtimeRecords()
    {
        return $this->hasMany(OvertimeRecord::class);
    }
}
