<?php

namespace App\Models;

use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(AuditObserver::class)]
class Loan extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'principal_amount',
        'installment_amount',
        'total_repaid_amount',
        'loan_status',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:4',
        'installment_amount' => 'decimal:4',
        'total_repaid_amount' => 'decimal:4',
    ];

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
