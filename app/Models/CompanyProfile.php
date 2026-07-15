<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasUuids;

    protected $table = 'company_profile';

    protected $fillable = [
        'company_id',
        'company_name',
        'tin',
        'registration_number',
        'address',
        'phone',
        'email',
        'working_days_per_month',
        'financial_year_start_month',
        'sdl_enabled',
        'wcf_enabled',
        'sdl_employee_threshold',
    ];

    protected function casts(): array
    {
        return [
            'sdl_enabled' => 'boolean',
            'wcf_enabled' => 'boolean',
            'working_days_per_month' => 'integer',
            'financial_year_start_month' => 'integer',
            'sdl_employee_threshold' => 'integer',
        ];
    }
}
