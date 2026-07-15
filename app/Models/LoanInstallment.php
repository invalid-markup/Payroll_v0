<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    use HasUuids;

    protected $fillable = [
        'loan_id',
        'payroll_period_id',
        'amount_deducted',
        'outstanding_balance_before',
        'outstanding_balance_after',
    ];

    protected $casts = [
        'amount_deducted' => 'decimal:4',
        'outstanding_balance_before' => 'decimal:4',
        'outstanding_balance_after' => 'decimal:4',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}