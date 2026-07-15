<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSchemeEnrollment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'employee_scheme_enrollments';

    protected $fillable = [
        'employee_id',
        'scheme_code',
        'membership_number',
        'effective_from',
    ];

    protected $casts = [
        'effective_from' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
