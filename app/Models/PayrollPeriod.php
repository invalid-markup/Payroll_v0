<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasUuids;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'process_date',
        'days_in_period',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'process_date' => 'date',
    ];

    public function runs()
    {
        return $this->hasMany(PayrollRun::class, 'payroll_period_id');
    }

    /**
     * Alias for runs() to allow consistent method naming across codebase.
     */
    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class, 'payroll_period_id');
    }
}
