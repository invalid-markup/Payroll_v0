<?php

namespace App\Http\Resources\Statutory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayeBracketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'minimum_income' => (float) $this->minimum_income,
            'maximum_income' => $this->maximum_income ? (float) $this->maximum_income : null,
            'rate_percentage' => (float) $this->rate_percentage,
            'base_tax_amount' => (float) $this->base_tax_amount,
            'effective_from' => $this->effective_from->format('Y-m-d'),
        ];
    }
}
