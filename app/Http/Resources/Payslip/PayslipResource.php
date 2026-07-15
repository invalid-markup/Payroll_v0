<?php

namespace App\Http\Resources\Payslip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'payroll_period_id' => $this->payrollRun->payroll_period_id ?? null,
            'gross_salary' => (float) $this->gross_salary_amount,
            'total_taxable_earnings' => (float) $this->taxable_income_amount,
            'paye_amount' => (float) $this->paye_tax_amount,
            'nssf_employee_amount' => (float) $this->nssf_deduction_amount,
            'total_pre_tax_deductions' => (float) $this->total_deductions_amount, // simplificiation for API match
            'total_post_tax_deductions' => 0, // placeholder
            'net_salary' => (float) $this->net_salary_amount,
            'rounding_adjustment' => (float) $this->rounding_adjustment,
            'earnings_breakdown' => [],
            'deductions_breakdown' => [],
        ];
    }
}
