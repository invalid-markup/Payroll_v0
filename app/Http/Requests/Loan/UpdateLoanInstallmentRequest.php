<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['hr_manager', 'finance_manager']);
    }

    public function rules(): array
    {
        return [
            'installment_amount' => ['required', 'numeric', 'min:1'],
        ];
    }
}
