<?php

namespace App\Models;

use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AuditObserver::class)]
class StatutoryConfiguration extends Model
{
    use HasUuids;

    protected $table = 'statutory_configurations';

    protected $fillable = [
        'code',
        'name',
        'rate_percentage',
        'flat_amount',
        'effective_from',
        'meta',
    ];

    protected $casts = [
        'rate_percentage' => 'decimal:4',
        'flat_amount' => 'decimal:4',
        'effective_from' => 'date',
        'meta' => 'array',
    ];
}
