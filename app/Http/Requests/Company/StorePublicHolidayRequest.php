<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('system_administrator');
    }

    public function rules(): array
    {
        // company_id comes from the authenticated token, not the payload
        $companyId = $this->user()?->currentAccessToken()?->abilities
            ? collect($this->user()->currentAccessToken()->abilities)
                ->first(fn ($a) => str_starts_with($a, 'company:'))
            : null;
        $companyId = $companyId ? substr($companyId, 8) : null;

        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                // Duplicate date per company blocked at DB level (uq_public_holidays_company_date)
                // but we add a soft check here for a user-friendly error message.
                Rule::unique('public_holidays', 'date')
                    ->where('company_id', $companyId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
