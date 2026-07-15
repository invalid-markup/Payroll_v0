<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['hr_manager', 'hr_officer']);
    }

    public function rules(): array
    {
        return [
            'leave_type' => ['sometimes', 'required', 'string', 'in:annual,sick,maternity,paternity,unpaid,unauthorized_absence'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'days' => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
        ];
    }
}
