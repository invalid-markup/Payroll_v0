<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasUuids;

    protected $table = 'public_holidays';

    protected $fillable = [
        'company_id',
        'date',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
