<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRecord extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'integer',
    ];
}
