<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicHolidayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'date' => $this->date?->toDateString(),
            'name' => $this->name,
        ];
    }
}
