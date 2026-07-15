<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ReactivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['system_administrator', 'hr_manager']);
    }

    public function rules(): array
    {
        return [
            'hire_date' => ['required', 'date'],
        ];
    }
}
