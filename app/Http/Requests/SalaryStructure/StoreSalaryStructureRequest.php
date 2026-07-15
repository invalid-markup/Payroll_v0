<?php

namespace App\Http\Requests\SalaryStructure;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['system_administrator', 'hr_manager']);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:salary_structures,code'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
