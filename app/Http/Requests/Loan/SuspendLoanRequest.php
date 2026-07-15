<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class SuspendLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['finance_manager']);
    }

    public function rules(): array
    {
        return [
            'payroll_period_id' => ['required', 'uuid', 'exists:payroll_periods,id'],
        ];
    }
}
