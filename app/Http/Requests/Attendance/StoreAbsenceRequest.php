<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
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
            'days' => ['required', 'integer', 'min:1'],
        ];
    }
}
