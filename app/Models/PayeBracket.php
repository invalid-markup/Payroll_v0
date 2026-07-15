<?php

namespace App\Models;

use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AuditObserver::class)]
class PayeBracket extends Model
{
    use HasUuids;

    protected $table = 'paye_brackets';

    protected $fillable = [
        'minimum_income',
        'maximum_income',
        'rate_percentage',
        'base_tax_amount',
        'effective_from',
    ];

    protected $casts = [
        'minimum_income' => 'decimal:4',
        'maximum_income' => 'decimal:4',
        'rate_percentage' => 'decimal:4',
        'base_tax_amount' => 'decimal:4',
        'effective_from' => 'date',
    ];
}
