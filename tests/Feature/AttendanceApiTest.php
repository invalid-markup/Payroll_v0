<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRecord;
use App\Models\OvertimeRecord;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $hrManager;

    private Employee $employee;

    private PayrollPeriod $payrollPeriod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->hrManager = User::factory()->create();
        $this->hrManager->assignRole('hr_manager');

        $this->employee = Employee::create([
            'id' => Str::uuid(),
            'employee_number' => 'EMP-003',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'status' => 'active',
            'employment_type' => 'permanent',
            'resident_status' => 'resident',
        ]);

        $this->payrollPeriod = PayrollPeriod::create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => 'open',
            'company_id' => Str::uuid(), // Using mock company ID
            'process_date' => '2026-07-28',
            'days_in_period' => 31,
        ]);
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->payrollPeriod->company_id]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_hr_manager_can_get_attendance_records()
    {
        OvertimeRecord::create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'overtime_type' => 'hours_based',
            'hours' => 4,
        ]);

        LeaveRecord::create([
            'employee_id' => $this->employee->id,
            'leave_type' => 'unauthorized_absence',
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-15',
            'total_days' => 1,
        ]);

        $response = $this->actingAsUser($this->hrManager)
            ->getJson('/api/v1/attendance');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_hr_manager_can_create_overtime()
    {
        $response = $this->actingAsUser($this->hrManager)
            ->postJson('/api/v1/attendance/overtime', [
                'employee_id' => $this->employee->id,
                'date' => '2026-07-15',
                'hours' => 4,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'overtime');

        $this->assertDatabaseHas('overtime_records', [
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'hours' => '4',
        ]);
    }

    public function test_hr_manager_can_create_absence()
    {
        $response = $this->actingAsUser($this->hrManager)
            ->postJson('/api/v1/attendance/absence', [
                'employee_id' => $this->employee->id,
                'date' => '2026-07-20',
                'days' => 2,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'absence');

        $this->assertDatabaseHas('leave_records', [
            'employee_id' => $this->employee->id,
            'leave_type' => 'unauthorized_absence',
            'total_days' => 2,
            'start_date' => '2026-07-20 00:00:00',
            'end_date' => '2026-07-21 00:00:00',
        ]);
    }

    public function test_hr_manager_can_delete_attendance_record()
    {
        $overtime = OvertimeRecord::create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->payrollPeriod->id,
            'overtime_type' => 'hours_based',
            'hours' => 4,
        ]);

        $response = $this->actingAsUser($this->hrManager)
            ->deleteJson('/api/v1/attendance/'.$overtime->id);

        $response->assertNoContent();

        $this->assertSoftDeleted('overtime_records', [
            'id' => $overtime->id,
        ]);
    }
}
