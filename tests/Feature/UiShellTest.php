<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PublicHoliday;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UiShellTest extends TestCase
{
    use RefreshDatabase;

    private string $companyId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->companyId = (string) Str::uuid();

        CompanyProfile::create([
            'company_id' => $this->companyId,
            'company_name' => 'Acme Payroll Ltd',
            'tin' => '123-456-789',
            'registration_number' => 'REG-001',
            'address' => 'Dar es Salaam',
            'phone' => '+255700000000',
            'email' => 'info@acme.test',
            'working_days_per_month' => 26,
            'financial_year_start_month' => 7,
            'sdl_enabled' => true,
            'wcf_enabled' => true,
            'sdl_employee_threshold' => 10,
        ]);

        PublicHoliday::create([
            'company_id' => $this->companyId,
            'date' => '2026-12-09',
            'name' => 'Independence Day',
        ]);

        PayrollPeriod::create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->companyId,
            'name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'open',
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->companyId,
        ]);
        $this->admin->assignRole('system_administrator');
    }

    public function test_login_page_shows_reset_copy_when_reset_route_is_missing(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Forgot password? Contact your administrator.');
    }

    public function test_core_admin_pages_render_with_tenant_data(): void
    {
        $this->actingAs($this->admin)
            ->get('/company')
            ->assertOk()
            ->assertSee('Acme Payroll Ltd')
            ->assertSee('Public Holidays');

        $this->actingAs($this->admin)
            ->get('/audit-logs')
            ->assertOk()
            ->assertSee('Locked Record');

        $this->actingAs($this->admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('My Profile');

        $this->actingAs($this->admin)
            ->get('/payslips')
            ->assertOk()
            ->assertSee('My Payslips');
    }

    public function test_employee_pages_render_without_invalid_department_scoping(): void
    {
        $branch = Branch::create([
            'code' => 'DAR-001',
            'name' => 'Dar es Salaam',
        ]);

        $department = Department::create([
            'code' => 'PAY-001',
            'name' => 'Payroll',
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/employees')
            ->assertOk()
            ->assertSee('Payroll');

        $this->actingAs($this->admin)
            ->get('/employees/create')
            ->assertOk()
            ->assertSee('Payroll');
    }

    public function test_filed_payroll_run_page_shows_hard_record_badge_and_amend_action(): void
    {
        $period = PayrollPeriod::where('company_id', $this->companyId)->firstOrFail();

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'filed',
            'metadata' => [
                'filed_at' => now()->toIso8601String(),
            ],
        ]);

        $this->actingAs($this->admin)
            ->get('/payroll-runs/'.$run->id)
            ->assertOk()
            ->assertSee('Locked Record')
            ->assertSee('Initiate Amended Return');
    }

    public function test_audit_log_page_renders_real_entries(): void
    {
        $period = PayrollPeriod::where('company_id', $this->companyId)->firstOrFail();

        $run = PayrollRun::create([
            'payroll_period_id' => $period->id,
            'company_id' => $this->companyId,
            'type' => 'standard',
            'status' => 'draft',
        ]);

        AuditLog::create([
            'user_id' => $this->admin->id,
            'audit_event_type' => 'created',
            'model' => PayrollRun::class,
            'model_id' => $run->id,
            'ip_address' => '127.0.0.1',
            'old_values' => null,
            'new_values' => ['status' => 'draft'],
        ]);

        $this->actingAs($this->admin)
            ->get('/audit-logs')
            ->assertOk()
            ->assertSee('created')
            ->assertSee((string) $run->id);
    }
}
