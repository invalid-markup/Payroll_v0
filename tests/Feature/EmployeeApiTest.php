<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $hrManager;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->hrManager = User::factory()->create();
        $this->hrManager->assignRole('hr_manager');
    }

    private function actingAsWithCompany(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_can_create_employee(): void
    {
        $branch = Branch::create(['id' => Str::uuid(), 'code' => 'BR-001', 'name' => 'Dar Branch']);
        $department = Department::create(['id' => Str::uuid(), 'branch_id' => $branch->id, 'code' => 'DEP-001', 'name' => 'Finance']);

        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP-001',
                'first_name' => 'Asha',
                'last_name' => 'Mushi',
                'status' => 'active',
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'employment_type' => 'permanent',
                'resident_status' => 'resident',
                'tin' => '123-456-789',
                'nssf_number' => 'NSSF-001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.employee_number', 'EMP-001');

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP-001',
            'first_name' => 'Asha',
        ]);
    }
}
