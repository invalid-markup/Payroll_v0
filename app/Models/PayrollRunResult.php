<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayrollRunResult extends Model
{
    use HasUuids;

    protected $table = 'payroll_run_results';

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'processing_status',
        'basic_salary_amount',
        'gross_salary_amount',
        'taxable_income_amount',
        'nssf_deduction_amount',
        'paye_tax_amount',
        'total_deductions_amount',
        'net_salary_amount',
        'rounding_adjustment',
        'calculation_snapshot',
    ];

    protected $casts = [
        'basic_salary_amount' => 'decimal:4',
        'gross_salary_amount' => 'decimal:4',
        'taxable_income_amount' => 'decimal:4',
        'nssf_deduction_amount' => 'decimal:4',
        'paye_tax_amount' => 'decimal:4',
        'total_deductions_amount' => 'decimal:4',
        'net_salary_amount' => 'decimal:4',
        'rounding_adjustment' => 'decimal:4',
        'calculation_snapshot' => 'array',
    ];

    public function lineItems()
    {
        return $this->hasMany(PayslipLineItem::class, 'payroll_run_result_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }
}
