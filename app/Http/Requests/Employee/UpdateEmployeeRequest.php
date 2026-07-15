<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['system_administrator', 'hr_manager', 'hr_officer']);
    }

    public function rules(): array
    {
        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_number')->ignore($this->route('id')),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,terminated'],
            'employment_type' => ['required', 'in:permanent,contract,casual'],
            'resident_status' => ['required', 'in:resident,non_resident'],
            'secondary_employment_flag' => ['boolean'],
            'hire_date' => ['nullable', 'date'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'tin' => ['nullable', 'string', 'max:50'],
            'nssf_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
