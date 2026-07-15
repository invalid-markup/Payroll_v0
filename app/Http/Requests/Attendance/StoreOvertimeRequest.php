<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['hr_manager', 'hr_officer']);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'hours' => ['required_without:fixed_amount', 'nullable', 'numeric', 'min:0'],
            'fixed_amount' => ['required_without:hours', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
