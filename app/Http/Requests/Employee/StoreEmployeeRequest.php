<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['system_administrator', 'hr_manager', 'hr_officer']);
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50', 'unique:employees,employee_number'],
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

            'bank_details' => ['nullable', 'array'],
            'bank_details.*.bank_id' => ['required', 'uuid', 'exists:banks,id'],
            'bank_details.*.branch_code' => ['nullable', 'string', 'max:50'],
            'bank_details.*.account_number' => ['required', 'string', 'max:50'],
            'bank_details.*.is_primary' => ['boolean'],

            'scheme_enrollments' => ['nullable', 'array'],
            'scheme_enrollments.*.scheme_code' => ['required', 'in:nssf_employer,nssf_employee,wcf,sdl,heslb,zssf_employer,zssf_employee,nhif_employer,nhif_employee,pssfp_employer,pssfp_employee,workers_union'],
            'scheme_enrollments.*.membership_number' => ['nullable', 'string', 'max:50'],
            'scheme_enrollments.*.effective_from' => ['required', 'date'],
        ];
    }
}
