<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifies compliance with docs/06_SECURITY_SPECIFICATION.md
 * - §3.3  Maker/Checker: Policy rejects same user approving own submission
 * - §5.1  Audit logging: Sensitive model mutations write audit rows
 * - §5.2  Audit immutability: AuditLog::delete() throws
 * - §7.1  Unauthenticated requests receive 401
 * - §10   Authorization: cross-role access returns 403
 */
class SecurityComplianceTest extends TestCase
{
    use RefreshDatabase;

    private string $companyId;

    private User $payrollOfficer;

    private User $financeManager;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->payrollOfficer = User::factory()->create();
        $this->payrollOfficer->assignRole('payroll_officer');

        $this->financeManager = User::factory()->create();
        $this->financeManager->assignRole('finance_manager');

        $this->period = PayrollPeriod::create([
            'name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'open',
            'company_id' => $this->companyId,
            'process_date' => '2026-09-28',
            'days_in_period' => 30,
        ]);

        $this->employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP-SEC-01',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
        ]);
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    // ─── §7.1: Authentication ─────────────────────────────────────────────────

    public function test_unauthenticated_request_gets_401()
    {
        $this->getJson('/api/v1/loans')->assertUnauthorized();
    }

    // ─── §3.3: Maker/Checker ──────────────────────────────────────────────────

    public function test_payroll_officer_maker_cannot_approve_own_submission()
    {
        // Finance manager creates and submits the run (Maker)
        $run = PayrollRun::create([
            'payroll_period_id' => $this->period->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'preview',
            'submitted_by_user_id' => $this->financeManager->id,
        ]);

        // Same finance manager tries to approve → must be blocked by Policy
        $response = $this->actingAsUser($this->financeManager)
            ->postJson('/api/v1/payroll-runs/'.$run->id.'/approve');

        $response->assertForbidden();
    }

    public function test_different_checker_can_approve_run()
    {
        $checker = User::factory()->create();
        $checker->assignRole('finance_manager');

        $run = PayrollRun::create([
            'payroll_period_id' => $this->period->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'preview',
            'submitted_by_user_id' => $this->financeManager->id,
        ]);

        $response = $this->actingAsUser($checker)
            ->postJson('/api/v1/payroll-runs/'.$run->id.'/approve');

        $response->assertOk();
        $this->assertDatabaseHas('payroll_runs', [
            'id' => $run->id,
            'status' => 'approved',
            'approved_by_user_id' => $checker->id,
        ]);
    }

    // ─── §10: Cross-role access blocked ───────────────────────────────────────

    public function test_payroll_officer_cannot_create_loan()
    {
        $response = $this->actingAsUser($this->payrollOfficer)
            ->postJson('/api/v1/loans', [
                'employee_id' => $this->employee->id,
                'total_amount' => 1000000,
                'installment_amount' => 100000,
                'start_period_id' => $this->period->id,
            ]);

        $response->assertForbidden();
    }

    // ─── §5.1: Audit logging ──────────────────────────────────────────────────

    public function test_creating_a_loan_writes_an_audit_log_entry()
    {
        $this->actingAs($this->financeManager);

        Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 1000000,
            'installment_amount' => 100000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'model' => Loan::class,
            'audit_event_type' => 'created',
        ]);
    }

    public function test_updating_a_loan_writes_an_audit_log_entry()
    {
        $this->actingAs($this->financeManager);

        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 1000000,
            'installment_amount' => 100000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $loan->update(['loan_status' => 'suspended']);

        $this->assertDatabaseHas('audit_logs', [
            'model' => Loan::class,
            'audit_event_type' => 'updated',
        ]);
    }

    // ─── §5.2: Audit immutability ─────────────────────────────────────────────

    public function test_audit_log_cannot_be_deleted()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Audit logs are immutable');

        $log = AuditLog::create([
            'audit_event_type' => 'created',
            'model' => 'TestModel',
            'model_id' => Str::uuid(),
            'ip_address' => '127.0.0.1',
        ]);

        $log->delete();
    }
}
