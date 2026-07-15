<?php

namespace App\Http\Resources\Statutory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatutoryConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'rate_percentage' => (float) $this->rate_percentage,
            'effective_from' => $this->effective_from->format('Y-m-d'),
        ];
    }
}
