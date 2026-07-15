<?php

namespace App\Http\Resources\PayrollRun;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_period_id' => $this->payroll_period_id,
            'company_id' => $this->company_id,
            'type' => $this->type,
            'status' => $this->status,
            'submitted_by_user_id' => $this->submitted_by_user_id,
            'approved_by_user_id' => $this->approved_by_user_id,
            'original_run_id' => $this->original_run_id,
        ];
    }
}
