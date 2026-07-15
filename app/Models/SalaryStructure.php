<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'basic_salary',
        'currency',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:4',
    ];
}
