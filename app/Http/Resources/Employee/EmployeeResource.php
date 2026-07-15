<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'job_title' => $this->job_title,
            'status' => $this->status,
            'employment_type' => $this->employment_type,
            'resident_status' => $this->resident_status,
            'secondary_employment_flag' => (bool) $this->secondary_employment_flag,
            'hire_date' => $this->hire_date?->toDateString(),
            'termination_date' => $this->termination_date?->toDateString(),
            'tin' => $this->tin,
            'nssf_number' => $this->nssf_number,

            // Relationships
            'department' => [
                'id' => $this->department_id,
                'name' => $this->department?->name,
            ],
            'branch' => [
                'id' => $this->branch_id,
                'name' => $this->branch?->name,
            ],

            'bank_details' => $this->bankDetails ? $this->bankDetails->map(fn ($b) => [
                'bank_id' => $b->bank_id,
                'branch_code' => $b->branch_code,
                'account_number' => $b->account_number,
                'is_primary' => (bool) $b->is_primary,
            ]) : [],

            'scheme_enrollments' => $this->schemeEnrollments ? $this->schemeEnrollments->map(fn ($s) => [
                'scheme_code' => $s->scheme_code,
                'membership_number' => $s->membership_number,
                'effective_from' => $s->effective_from?->toDateString(),
            ]) : [],
        ];
    }
}
