<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    use RefreshDatabase;

    private User $financeManager;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->financeManager = User::factory()->create();
        $this->financeManager->assignRole('finance_manager');

        $this->employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => Str::uuid(),
            'employee_number' => 'EMP-004',
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
        ]);
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->employee->company_id]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_can_list_loans()
    {
        Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 1000000,
            'installment_amount' => 100000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $response = $this->actingAsUser($this->financeManager)
            ->getJson('/api/v1/loans');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_loan()
    {
        $period = PayrollPeriod::create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
            'company_id' => $this->employee->company_id,
            'process_date' => '2026-07-28',
            'days_in_period' => 31,
        ]);

        $response = $this->actingAsUser($this->financeManager)
            ->postJson('/api/v1/loans', [
                'employee_id' => $this->employee->id,
                'total_amount' => 1000000,
                'installment_amount' => 100000,
                'start_period_id' => $period->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('loans', [
            'employee_id' => $this->employee->id,
            'principal_amount' => '1000000.0000',
            'installment_amount' => '100000.0000',
            'loan_status' => 'active',
        ]);
    }

    public function test_cannot_create_multiple_active_loans()
    {
        Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 500000,
            'installment_amount' => 50000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
            'company_id' => $this->employee->company_id,
            'process_date' => '2026-07-28',
            'days_in_period' => 31,
        ]);

        $response = $this->actingAsUser($this->financeManager)
            ->postJson('/api/v1/loans', [
                'employee_id' => $this->employee->id,
                'total_amount' => 1000000,
                'installment_amount' => 100000,
                'start_period_id' => $period->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_loan_installment()
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 1000000,
            'installment_amount' => 100000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $response = $this->actingAsUser($this->financeManager)
            ->putJson('/api/v1/loans/'.$loan->id.'/installment', [
                'installment_amount' => 150000,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'installment_amount' => '150000.0000',
        ]);
    }

    public function test_can_suspend_loan()
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 1000000,
            'installment_amount' => 100000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
            'company_id' => $this->employee->company_id,
            'process_date' => '2026-07-28',
            'days_in_period' => 31,
        ]);

        $response = $this->actingAsUser($this->financeManager)
            ->postJson('/api/v1/loans/'.$loan->id.'/suspend', [
                'payroll_period_id' => $period->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'loan_status' => 'suspended',
        ]);
    }

    public function test_can_close_loan()
    {
        $loan = Loan::create([
            'employee_id' => $this->employee->id,
            'principal_amount' => 1000000,
            'installment_amount' => 100000,
            'total_repaid_amount' => 0,
            'loan_status' => 'active',
        ]);

        $response = $this->actingAsUser($this->financeManager)
            ->postJson('/api/v1/loans/'.$loan->id.'/close');

        $response->assertOk();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'loan_status' => 'closed',
        ]);
    }
}
