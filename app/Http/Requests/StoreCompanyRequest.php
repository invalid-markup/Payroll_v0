<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'regex:/^\d{3}-\d{3}-\d{3}$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'working_days_per_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'financial_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'sdl_enabled' => ['nullable', 'boolean'],
            'wcf_enabled' => ['nullable', 'boolean'],
            'sdl_employee_threshold' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
