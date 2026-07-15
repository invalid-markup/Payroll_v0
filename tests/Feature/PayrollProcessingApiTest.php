<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollProcessingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $payrollOfficer;

    private User $financeManager;

    private User $sysAdmin;

    private PayrollPeriod $payrollPeriod;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->payrollOfficer = User::factory()->create();
        $this->payrollOfficer->assignRole('payroll_officer');

        $this->financeManager = User::factory()->create();
        $this->financeManager->assignRole('finance_manager');

        $this->sysAdmin = User::factory()->create();
        $this->sysAdmin->assignRole('system_administrator');

        $this->payrollPeriod = PayrollPeriod::create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
            'company_id' => $this->companyId,
            'process_date' => '2026-07-28',
            'days_in_period' => 31,
        ]);
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_can_create_payroll_run()
    {
        $response = $this->actingAsUser($this->payrollOfficer)
            ->postJson('/api/v1/payroll-runs', [
                'payroll_period_id' => $this->payrollPeriod->id,
                'type' => 'standard',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('payroll_runs', [
            'payroll_period_id' => $this->payrollPeriod->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'draft',
        ]);
    }

    public function test_can_validate_payroll_run()
    {
        $run = PayrollRun::create([
            'payroll_period_id' => $this->payrollPeriod->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'draft',
        ]);

        $response = $this->actingAsUser($this->payrollOfficer)
            ->postJson('/api/v1/payroll-runs/'.$run->id.'/validate');

        $response->assertOk()
            ->assertJsonPath('data.status', 'validated');
    }

    public function test_can_submit_payroll_run()
    {
        $run = PayrollRun::create([
            'payroll_period_id' => $this->payrollPeriod->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'preview',
        ]);

        $response = $this->actingAsUser($this->payrollOfficer)
            ->postJson('/api/v1/payroll-runs/'.$run->id.'/submit');

        $response->assertOk()
            ->assertJsonPath('data.status', 'preview')
            ->assertJsonPath('data.submitted_by_user_id', $this->payrollOfficer->id);
    }

    public function test_finance_manager_can_approve_payroll_run()
    {
        $run = PayrollRun::create([
            'payroll_period_id' => $this->payrollPeriod->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'preview',
            'submitted_by_user_id' => $this->payrollOfficer->id, // Maker
        ]);

        $response = $this->actingAsUser($this->financeManager) // Checker
            ->postJson('/api/v1/payroll-runs/'.$run->id.'/approve');

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by_user_id', $this->financeManager->id);
    }

    public function test_sysadmin_can_reverse_payroll_run()
    {
        $run = PayrollRun::create([
            'payroll_period_id' => $this->payrollPeriod->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'filed',
        ]);

        $response = $this->actingAsUser($this->sysAdmin)
            ->postJson('/api/v1/payroll-runs/'.$run->id.'/reverse');

        $response->assertOk()
            ->assertJsonPath('data.status', 'reversed');
    }
}
