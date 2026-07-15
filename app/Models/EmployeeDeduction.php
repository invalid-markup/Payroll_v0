<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'payroll_period_id',
        'amount',
        'percentage',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'percentage' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function deductionType()
    {
        return $this->belongsTo(DeductionType::class);
    }
}
