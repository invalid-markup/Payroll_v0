<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class TerminateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['system_administrator', 'hr_manager']);
    }

    public function rules(): array
    {
        return [
            'termination_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'acknowledge_loans' => ['boolean'],
        ];
    }
}
