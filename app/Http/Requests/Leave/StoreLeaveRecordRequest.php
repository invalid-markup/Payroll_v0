<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['hr_manager', 'hr_officer']);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'leave_type' => ['required', 'string', 'in:annual,sick,maternity,paternity,unpaid,unauthorized_absence'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'integer', 'min:1', 'max:31'],
        ];
    }
}
