<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRecord;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeaveManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $hrManager;

    private User $employeeUser;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->hrManager = User::factory()->create();
        $this->hrManager->assignRole('hr_manager');

        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');

        $this->employee = Employee::create([
            'id' => Str::uuid(),
            'employee_number' => 'EMP-002',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
        ]);
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.Str::uuid()->toString()]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_hr_manager_can_get_leave_records()
    {
        LeaveRecord::factory()->create(['employee_id' => $this->employee->id]);

        $response = $this->actingAsUser($this->hrManager)
            ->getJson('/api/v1/leave');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_hr_manager_can_create_leave_record()
    {
        $response = $this->actingAsUser($this->hrManager)
            ->postJson('/api/v1/leave', [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'days' => 5,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.employee_id', $this->employee->id);

        $this->assertDatabaseHas('leave_records', [
            'employee_id' => $this->employee->id,
            'leave_type' => 'annual',
            'total_days' => 5,
        ]);
    }

    public function test_hr_manager_can_update_leave_record()
    {
        $leave = LeaveRecord::factory()->create(['employee_id' => $this->employee->id, 'leave_type' => 'annual']);

        $response = $this->actingAsUser($this->hrManager)
            ->putJson('/api/v1/leave/'.$leave->id, [
                'leave_type' => 'sick',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.leave_type', 'sick');

        $this->assertDatabaseHas('leave_records', [
            'id' => $leave->id,
            'leave_type' => 'sick',
        ]);
    }

    public function test_hr_manager_can_delete_leave_record()
    {
        $leave = LeaveRecord::factory()->create(['employee_id' => $this->employee->id]);

        $response = $this->actingAsUser($this->hrManager)
            ->deleteJson('/api/v1/leave/'.$leave->id);

        $response->assertNoContent();

        $this->assertSoftDeleted('leave_records', [
            'id' => $leave->id,
        ]);
    }

    public function test_employee_cannot_create_leave_record()
    {
        $response = $this->actingAsUser($this->employeeUser)
            ->postJson('/api/v1/leave', [
                'employee_id' => $this->employee->id,
                'leave_type' => 'annual',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'days' => 5,
            ]);

        $response->assertForbidden();
    }
}
