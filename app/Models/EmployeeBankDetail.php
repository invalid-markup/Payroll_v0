<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeBankDetail extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'employee_bank_details';

    protected $fillable = [
        'employee_id',
        'bank_id',
        'branch_code',
        'account_number',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
