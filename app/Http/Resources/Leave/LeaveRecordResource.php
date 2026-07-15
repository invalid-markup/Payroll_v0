<?php

namespace App\Http\Resources\Leave;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'days' => $this->total_days,
        ];
    }
}
