<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryHistory;
use App\Models\SalaryStructure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalaryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $hrManager;

    private User $hrOfficer;

    private User $payrollOfficer;

    private string $companyId;

    private Employee $employee;

    private SalaryStructure $structure;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->hrManager = User::factory()->create();
        $this->hrManager->assignRole('hr_manager');

        $this->hrOfficer = User::factory()->create();
        $this->hrOfficer->assignRole('hr_officer');

        $this->payrollOfficer = User::factory()->create();
        $this->payrollOfficer->assignRole('payroll_officer');

        $branch = Branch::create(['id' => Str::uuid(), 'code' => 'B01', 'name' => 'HQ']);
        $dept = Department::create(['id' => Str::uuid(), 'code' => 'D01', 'name' => 'Finance', 'branch_id' => $branch->id]);

        $this->employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
            'department_id' => $dept->id,
            'branch_id' => $branch->id,
        ]);

        $this->structure = SalaryStructure::create([
            'id' => Str::uuid(),
            'code' => 'GRD-A',
            'name' => 'Grade A Executive',
            'currency' => 'TZS',
        ]);
    }

    private function actingAsWithCompany(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    // ── GET /salary-structures ────────────────────────────────────────────────

    public function test_payroll_officer_can_list_salary_structures()
    {
        $response = $this->actingAsWithCompany($this->payrollOfficer)
            ->getJson('/api/v1/salary-structures');

        $response->assertOk()
            ->assertJsonPath('data.0.code', 'GRD-A');
    }

    // ── POST /salary-structures ───────────────────────────────────────────────

    public function test_hr_manager_can_create_salary_structure()
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson('/api/v1/salary-structures', [
                'code' => 'GRD-B',
                'name' => 'Grade B Senior',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'GRD-B');

        $this->assertDatabaseHas('salary_structures', ['code' => 'GRD-B']);
    }

    public function test_salary_structure_code_must_be_unique()
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson('/api/v1/salary-structures', [
                'code' => 'GRD-A', // already exists from setUp
                'name' => 'Duplicate Grade',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_hr_officer_cannot_create_salary_structure()
    {
        $response = $this->actingAsWithCompany($this->hrOfficer)
            ->postJson('/api/v1/salary-structures', [
                'code' => 'GRD-C',
                'name' => 'Grade C',
            ]);

        $response->assertForbidden();
    }

    // ── POST /employees/{id}/salary ───────────────────────────────────────────

    public function test_hr_manager_can_assign_salary_to_employee()
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson("/api/v1/employees/{$this->employee->id}/salary", [
                'basic_salary_amount' => 1500000,
                'salary_structure_id' => $this->structure->id,
                'effective_from' => '2026-01-01',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.basic_salary_amount', '1500000.0000');

        $this->assertDatabaseHas('salary_histories', [
            'employee_id' => $this->employee->id,
            'salary_structure_id' => $this->structure->id,
        ]);
    }

    public function test_salary_is_appended_never_overwritten()
    {
        // First salary record
        SalaryHistory::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'basic_salary_amount' => 1000000,
            'salary_structure_id' => $this->structure->id,
            'effective_from' => '2026-01-01',
        ]);

        // Add a second — should create a new record, not update
        $this->actingAsWithCompany($this->hrManager)
            ->postJson("/api/v1/employees/{$this->employee->id}/salary", [
                'basic_salary_amount' => 1200000,
                'salary_structure_id' => $this->structure->id,
                'effective_from' => '2026-07-01',
            ]);

        $this->assertEquals(2, SalaryHistory::where('employee_id', $this->employee->id)->count());
        $this->assertDatabaseHas('salary_histories', ['basic_salary_amount' => 1000000]);
        $this->assertDatabaseHas('salary_histories', ['basic_salary_amount' => 1200000]);
    }

    public function test_salary_requires_valid_salary_structure()
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson("/api/v1/employees/{$this->employee->id}/salary", [
                'basic_salary_amount' => 1500000,
                'salary_structure_id' => Str::uuid(), // non-existent
                'effective_from' => '2026-01-01',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['salary_structure_id']);
    }

    // ── GET /employees/{id}/salary-history ───────────────────────────────────

    public function test_payroll_officer_can_get_employee_salary_history()
    {
        SalaryHistory::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'basic_salary_amount' => 1000000,
            'salary_structure_id' => $this->structure->id,
            'effective_from' => '2026-01-01',
        ]);

        $response = $this->actingAsWithCompany($this->payrollOfficer)
            ->getJson("/api/v1/employees/{$this->employee->id}/salary-history");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_salary_history_is_ordered_descending()
    {
        SalaryHistory::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'basic_salary_amount' => 1000000,
            'salary_structure_id' => $this->structure->id,
            'effective_from' => '2026-01-01',
        ]);

        SalaryHistory::create([
            'id' => Str::uuid(),
            'employee_id' => $this->employee->id,
            'basic_salary_amount' => 1500000,
            'salary_structure_id' => $this->structure->id,
            'effective_from' => '2026-07-01',
        ]);

        $response = $this->actingAsWithCompany($this->payrollOfficer)
            ->getJson("/api/v1/employees/{$this->employee->id}/salary-history");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.effective_from', '2026-07-01') // newest first
            ->assertJsonPath('data.1.effective_from', '2026-01-01');
    }
}
