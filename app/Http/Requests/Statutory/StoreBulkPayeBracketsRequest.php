<?php

namespace App\Http\Requests\Statutory;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkPayeBracketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('system_administrator');
    }

    public function rules(): array
    {
        return [
            'effective_from' => ['required', 'date'],
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.minimum_income' => ['required', 'numeric', 'min:0'],
            'brackets.*.maximum_income' => ['nullable', 'numeric', 'gte:brackets.*.minimum_income'],
            'brackets.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'brackets.*.base_tax_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
