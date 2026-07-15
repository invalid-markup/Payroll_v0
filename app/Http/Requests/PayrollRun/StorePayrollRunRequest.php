<?php

namespace App\Http\Requests\PayrollRun;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['payroll_officer', 'finance_manager']);
    }

    public function rules(): array
    {
        return [
            'payroll_period_id' => ['required', 'uuid', 'exists:payroll_periods,id'],
            'type' => ['required', 'string', 'in:standard,supplementary,amended_return'],
        ];
    }
}
