<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->company_name,
            'tin' => $this->tin,
            'registration_number' => $this->registration_number,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'working_days_per_month' => $this->working_days_per_month,
            'financial_year_start_month' => $this->financial_year_start_month,
            'sdl_enabled' => $this->sdl_enabled,
            'wcf_enabled' => $this->wcf_enabled,
            'sdl_employee_threshold' => $this->sdl_employee_threshold,
        ];
    }
}
