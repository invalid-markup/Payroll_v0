<?php

namespace App\Http\Requests\Statutory;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatutoryConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('system_administrator');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'in:nssf_employer,nssf_employee,wcf,sdl'],
            'rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
