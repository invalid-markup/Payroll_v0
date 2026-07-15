<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunResult;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BankExportApiTest extends TestCase
{
    use RefreshDatabase;

    private User $financeManager;

    private User $payrollOfficer;

    private PayrollPeriod $period;

    private PayrollRun $approvedRun;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->financeManager = User::factory()->create();
        $this->financeManager->assignRole('finance_manager');

        $this->payrollOfficer = User::factory()->create();
        $this->payrollOfficer->assignRole('payroll_officer');

        $this->period = PayrollPeriod::create([
            'name' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
            'company_id' => $this->companyId,
            'process_date' => '2026-08-28',
            'days_in_period' => 31,
        ]);

        $this->approvedRun = PayrollRun::create([
            'payroll_period_id' => $this->period->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'approved',
        ]);

        $employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP-001',
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
        ]);

        $bank = Bank::create(['id' => Str::uuid(), 'code' => 'NMB', 'name' => 'NMB Bank']);

        EmployeeBankDetail::create([
            'employee_id' => $employee->id,
            'bank_id' => $bank->id,
            'account_number' => '1234567890',
            'branch_code' => '001',
            'is_primary' => true,
        ]);

        PayrollRunResult::create([
            'payroll_run_id' => $this->approvedRun->id,
            'employee_id' => $employee->id,
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

    public function test_finance_manager_can_validate_bank_export()
    {
        $response = $this->actingAsUser($this->financeManager)
            ->getJson('/api/v1/bank-export/validate?payroll_period_id='.$this->period->id);

        $response->assertOk()
            ->assertJsonPath('is_valid', true);
    }

    public function test_payroll_officer_cannot_download_bank_export()
    {
        $response = $this->actingAsUser($this->payrollOfficer)
            ->get('/api/v1/bank-export/download?payroll_period_id='.$this->period->id);

        $response->assertForbidden();
    }

    public function test_finance_manager_can_download_bank_export_and_hash_is_stored()
    {
        $response = $this->actingAsUser($this->financeManager)
            ->get('/api/v1/bank-export/download?payroll_period_id='.$this->period->id);

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // The X-File-Hash header should contain a 64-char SHA-256 hex string
        $hash = $response->headers->get('X-File-Hash');
        $this->assertNotNull($hash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);

        // A Hard Record should have been written to bank_exports
        $this->assertDatabaseHas('bank_exports', [
            'payroll_run_id' => $this->approvedRun->id,
            'generated_by_user_id' => $this->financeManager->id,
            'file_hash' => $hash,
            'total_records' => 1,
        ]);
    }
}
