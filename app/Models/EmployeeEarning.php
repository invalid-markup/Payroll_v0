<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeEarning extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'earning_type_id',
        'payroll_period_id',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function earningType()
    {
        return $this->belongsTo(EarningType::class);
    }
}
