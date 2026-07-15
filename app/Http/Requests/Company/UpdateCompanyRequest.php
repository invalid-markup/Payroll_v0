<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('system_administrator');
    }

    /**
     * Validation rules per 08_VALIDATION_SPECIFICATION.md §4 (Company).
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'regex:/^\d{3}-\d{3}-\d{3}$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'working_days_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'financial_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sdl_enabled' => ['boolean'],
            'wcf_enabled' => ['boolean'],
            'sdl_employee_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
