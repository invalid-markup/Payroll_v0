<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['hr_manager', 'finance_manager']);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'total_amount' => ['required', 'numeric', 'min:1'],
            'installment_amount' => ['required', 'numeric', 'min:1', 'lte:total_amount'],
            'start_period_id' => ['required', 'uuid', 'exists:payroll_periods,id'],
        ];
    }
}
