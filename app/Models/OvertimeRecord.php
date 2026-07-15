<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'approved_by_user_id',
        'overtime_type',
        'hours',
        'overtime_rate_multiplier',
        'fixed_amount',
    ];

    protected $casts = [
        'hours' => 'decimal:4',
        'overtime_rate_multiplier' => 'decimal:4',
        'fixed_amount' => 'decimal:4',
    ];
}
