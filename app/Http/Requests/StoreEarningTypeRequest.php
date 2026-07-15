<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEarningTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:earning_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:allowance,bonus,overtime,commission,incentive,custom'],
        ];
    }
}
