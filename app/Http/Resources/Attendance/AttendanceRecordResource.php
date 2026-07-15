<?php

namespace App\Http\Resources\Attendance;

use App\Models\OvertimeRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->resource instanceof OvertimeRecord ? 'overtime' : 'absence';

        if ($type === 'overtime') {
            return [
                'id' => $this->id,
                'type' => 'overtime',
                'employee_id' => $this->employee_id,
                'overtime_type' => $this->overtime_type,
                'hours' => $this->hours ? (float) $this->hours : null,
                'fixed_amount' => $this->fixed_amount ? (float) $this->fixed_amount : null,
            ];
        } else {
            return [
                'id' => $this->id,
                'type' => 'absence',
                'employee_id' => $this->employee_id,
                'date' => $this->start_date->format('Y-m-d'),
                'days' => $this->total_days,
            ];
        }
    }
}
