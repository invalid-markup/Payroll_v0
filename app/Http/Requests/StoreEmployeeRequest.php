<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50', 'unique:employees,employee_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,terminated'],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'employment_type' => ['nullable', 'in:permanent,contract,casual'],
            'resident_status' => ['nullable', 'in:resident,non_resident'],
            'tin' => ['nullable', 'regex:/^\d{3}-\d{3}-\d{3}$/'],
            'nssf_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
