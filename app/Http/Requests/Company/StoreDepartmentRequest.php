<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('system_administrator');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
        ];
    }
}
