<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['system_administrator', 'hr_manager']);
    }

    public function rules(): array
    {
        return [
            'basic_salary_amount' => ['required', 'numeric', 'min:0'],
            'salary_structure_id' => ['required', 'uuid', 'exists:salary_structures,id'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
