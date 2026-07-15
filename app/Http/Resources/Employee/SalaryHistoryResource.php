<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'basic_salary_amount' => $this->basic_salary_amount,
            'effective_from' => $this->effective_from?->toDateString(),
            'salary_structure' => [
                'id' => $this->salary_structure_id,
                'code' => $this->salaryStructure?->code,
                'name' => $this->salaryStructure?->name,
            ],
        ];
    }
}
