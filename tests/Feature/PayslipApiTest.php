<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunResult;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayslipApiTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    private User $hrManager;

    private Employee $employee;

    private PayrollPeriod $period;

    private PayrollRunResult $result;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->employeeUser = User::factory()->create();

        $this->hrManager = User::factory()->create();
        $this->hrManager->assignRole('hr_manager');

        $this->employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP-005',
            'first_name' => 'Sarah',
            'last_name' => 'Connor',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
        ]);

        $this->period = PayrollPeriod::create([
            'name' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
            'company_id' => $this->companyId,
            'process_date' => '2026-08-28',
            'days_in_period' => 31,
        ]);

        $run = PayrollRun::create([
            'payroll_period_id' => $this->period->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'locked',
        ]);

        $this->result = PayrollRunResult::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employee->id,
            'processing_status' => 'success',
            'basic_salary_amount' => 1500000,
            'gross_salary_amount' => 1500000,
            'taxable_income_amount' => 1500000,
            'nssf_deduction_amount' => 150000,
            'paye_tax_amount' => 200000,
            'total_deductions_amount' => 350000,
            'net_salary_amount' => 1150000,
            'rounding_adjustment' => 0,
            'calculation_snapshot' => [],
        ]);
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_can_list_payslips()
    {
        $response = $this->actingAsUser($this->hrManager)
            ->getJson('/api/v1/payslips');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_payslip_details()
    {
        $response = $this->actingAsUser($this->hrManager)
            ->getJson('/api/v1/payslips/'.$this->result->id);

        $response->assertOk()
            ->assertJsonPath('data.employee_id', $this->employee->id)
            ->assertJsonPath('data.gross_salary', 1500000);
    }

    public function test_can_export_payslip()
    {
        $response = $this->actingAsUser($this->hrManager)
            ->get('/api/v1/payslips/'.$this->result->id.'/export');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
