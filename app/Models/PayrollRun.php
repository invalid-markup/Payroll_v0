<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasUuids;

    protected $table = 'payroll_runs';

    protected $fillable = [
        'payroll_period_id',
        'company_id',
        'type',
        'status',
        'submitted_by_user_id',
        'approved_by_user_id',
        'original_run_id',
        'reversed_by_run_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'payroll_period_id' => 'string',
        'company_id' => 'string',
        'submitted_by_user_id' => 'string',
        'approved_by_user_id' => 'string',
        'original_run_id' => 'string',
        'reversed_by_run_id' => 'string',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /**
     * Alias for payrollPeriod() to preserve older references.
     */
    public function period()
    {
        return $this->payrollPeriod();
    }

    public function results()
    {
        return $this->hasMany(PayrollRunResult::class);
    }

    public function payrollEntries()
    {
        return $this->hasMany(PayrollRunResult::class, 'payroll_run_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function originalRun()
    {
        return $this->belongsTo(PayrollRun::class, 'original_run_id');
    }

    public function reversedByRun()
    {
        return $this->belongsTo(PayrollRun::class, 'reversed_by_run_id');
    }

    public function getRunTypeAttribute(): string
    {
        return (string) $this->type;
    }

    public function getTotalGrossPayAttribute(): string
    {
        return (string) $this->results()->sum('gross_salary_amount');
    }

    public function getTotalNetPayAttribute(): string
    {
        return (string) $this->results()->sum('net_salary_amount');
    }

    public function getTotalPayeAttribute(): string
    {
        return (string) $this->results()->sum('paye_tax_amount');
    }

    public function getTotalDeductionsAttribute(): string
    {
        return (string) $this->results()->sum('total_deductions_amount');
    }
}
