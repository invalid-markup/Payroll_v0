<?php

namespace App\Http\Resources\Loan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'total_amount' => (float) $this->principal_amount,
            'installment_amount' => (float) $this->installment_amount,
            'total_repaid_amount' => (float) $this->total_repaid_amount,
            'status' => $this->loan_status,
        ];
    }
}
