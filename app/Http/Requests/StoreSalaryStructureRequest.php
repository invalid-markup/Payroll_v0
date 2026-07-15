<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:salary_structures,code'],
            'name' => ['required', 'string', 'max:255'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
        ];
    }
}
