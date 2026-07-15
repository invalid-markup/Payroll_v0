<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PayslipLineItem extends Model
{
    use HasUuids;

    protected $table = 'payslip_line_items';

    protected $fillable = [
        'payroll_run_result_id',
        'type',
        'code',
        'name',
        'amount',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function result()
    {
        return $this->belongsTo(PayrollRunResult::class, 'payroll_run_result_id');
    }
}
