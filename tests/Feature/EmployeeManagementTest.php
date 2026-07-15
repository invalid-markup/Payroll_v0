<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $hrManager;

    private User $hrOfficer;

    private User $financeManager;

    private string $companyId;

    private Branch $branch;

    private Department $department;

    private Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->hrManager = User::factory()->create();
        $this->hrManager->assignRole('hr_manager');

        $this->hrOfficer = User::factory()->create();
        $this->hrOfficer->assignRole('hr_officer');

        $this->financeManager = User::factory()->create();
        $this->financeManager->assignRole('finance_manager');

        $this->branch = Branch::create(['id' => Str::uuid(), 'code' => 'B01', 'name' => 'Branch 1']);
        $this->department = Department::create(['id' => Str::uuid(), 'code' => 'D01', 'name' => 'HR', 'branch_id' => $this->branch->id]);
        $this->bank = Bank::create(['id' => Str::uuid(), 'code' => 'NMB', 'name' => 'NMB Bank']);
    }

    private function actingAsWithCompany(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_hr_officer_can_create_employee()
    {
        $response = $this->actingAsWithCompany($this->hrOfficer)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP001',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'status' => 'active',
                'employment_type' => 'permanent',
                'resident_status' => 'resident',
                'department_id' => $this->department->id,
                'branch_id' => $this->branch->id,
                'bank_details' => [
                    [
                        'bank_id' => $this->bank->id,
                        'account_number' => '1234567890',
                        'is_primary' => true,
                    ],
                ],
                'scheme_enrollments' => [
                    [
                        'scheme_code' => 'nssf_employee',
                        'membership_number' => 'NSSF123',
                        'effective_from' => '2026-01-01',
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.employee_number', 'EMP001');

        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP001']);
        $this->assertDatabaseHas('employee_bank_details', ['account_number' => '1234567890']);
        $this->assertDatabaseHas('employee_scheme_enrollments', ['membership_number' => 'NSSF123']);
    }

    public function test_employee_number_must_be_unique()
    {
        Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP001',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
            'department_id' => $this->department->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAsWithCompany($this->hrOfficer)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP001',
                'first_name' => 'New',
                'last_name' => 'User',
                'status' => 'active',
                'employment_type' => 'permanent',
                'resident_status' => 'resident',
                'department_id' => $this->department->id,
                'branch_id' => $this->branch->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_number']);
    }

    public function test_hr_manager_can_terminate_employee()
    {
        $employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP002',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
            'department_id' => $this->department->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson("/api/v1/employees/{$employee->id}/terminate", [
                'termination_date' => '2026-12-31',
                'reason' => 'Resigned',
            ]);

        $response->assertOk();
        $employee->refresh();
        $this->assertEquals('terminated', $employee->status);
        $this->assertEquals('2026-12-31', $employee->termination_date->format('Y-m-d'));
    }

    public function test_hr_officer_cannot_terminate_employee()
    {
        $employee = Employee::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'employee_number' => 'EMP003',
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
            'department_id' => $this->department->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAsWithCompany($this->hrOfficer)
            ->postJson("/api/v1/employees/{$employee->id}/terminate", [
                'termination_date' => '2026-12-31',
                'reason' => 'Resigned',
            ]);

        $response->assertForbidden();
    }
}
