<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BankExport extends Model
{
    use HasUuids;

    protected $table = 'bank_exports';

    protected $fillable = [
        'payroll_run_id',
        'generated_by_user_id',
        'file_hash',
        'total_records',
        'total_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    /**
     * Bank exports are Hard Records and cannot be deleted.
     */
    public function delete()
    {
        throw new \Exception('Bank export records are immutable and cannot be deleted.');
    }
}
