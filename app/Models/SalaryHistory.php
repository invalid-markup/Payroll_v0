<?php

namespace App\Models;

use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AuditObserver::class)]
class SalaryHistory extends Model
{
    use HasUuids;

    protected $table = 'salary_histories';

    protected $fillable = [
        'employee_id',
        'salary_structure_id',
        'basic_salary_amount',
        'effective_from',
    ];

    protected $casts = [
        'basic_salary_amount' => 'decimal:4',
        'effective_from' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }
}
