<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CompanyProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $sysAdmin;

    private User $regularUser;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->sysAdmin = User::factory()->create();
        $this->sysAdmin->assignRole('system_administrator');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('payroll_officer');
    }

    private function actingAsWithCompany(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    // ── GET /company ─────────────────────────────────────────────────────────

    public function test_any_user_can_get_company_profile()
    {
        CompanyProfile::create([
            'id' => Str::uuid(),
            'company_id' => $this->companyId,
            'company_name' => 'Test Corp',
            'working_days_per_month' => 22,
        ]);

        $response = $this->actingAsWithCompany($this->regularUser)
            ->getJson('/api/v1/company');

        $response->assertOk()
            ->assertJsonPath('data.name', 'Test Corp');
    }

    public function test_get_company_returns_404_when_not_set_up()
    {
        $response = $this->actingAsWithCompany($this->regularUser)
            ->getJson('/api/v1/company');

        $response->assertNotFound();
    }

    // ── PUT /company ─────────────────────────────────────────────────────────

    public function test_system_administrator_can_update_company_profile()
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->putJson('/api/v1/company', [
                'name' => 'PayEasy Tanzania Ltd',
                'tin' => '123-456-789',
                'working_days_per_month' => 22,
                'sdl_enabled' => true,
                'wcf_enabled' => false,
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'PayEasy Tanzania Ltd');

        $this->assertDatabaseHas('company_profile', ['company_name' => 'PayEasy Tanzania Ltd']);
    }

    public function test_non_admin_cannot_update_company()
    {
        $response = $this->actingAsWithCompany($this->regularUser)
            ->putJson('/api/v1/company', [
                'name' => 'Unauthorized Update',
                'working_days_per_month' => 22,
            ]);

        $response->assertForbidden();
    }

    public function test_company_update_validates_tin_format()
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->putJson('/api/v1/company', [
                'name' => 'Test Corp',
                'tin' => 'INVALID-TIN',
                'working_days_per_month' => 22,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tin']);
    }

    // ── POST /branches ────────────────────────────────────────────────────────

    public function test_system_administrator_can_create_branch()
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->postJson('/api/v1/branches', [
                'code' => 'DAR-01',
                'name' => 'Dar es Salaam HQ',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('branches', ['code' => 'DAR-01']);
    }

    public function test_branch_code_must_be_unique()
    {
        Branch::create(['id' => Str::uuid(), 'code' => 'DAR-01', 'name' => 'Existing']);

        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->postJson('/api/v1/branches', [
                'code' => 'DAR-01',
                'name' => 'Duplicate Branch',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    // ── POST /departments ─────────────────────────────────────────────────────

    public function test_system_administrator_can_create_department()
    {
        $branch = Branch::create(['id' => Str::uuid(), 'code' => 'DAR-01', 'name' => 'Dar HQ']);

        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->postJson('/api/v1/departments', [
                'code' => 'HR-01',
                'name' => 'Human Resources',
                'branch_id' => $branch->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('departments', ['code' => 'HR-01']);
    }

    public function test_department_requires_valid_branch_id()
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->postJson('/api/v1/departments', [
                'code' => 'HR-01',
                'name' => 'Human Resources',
                'branch_id' => Str::uuid(), // non-existent
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['branch_id']);
    }

    // ── GET /branches / departments ───────────────────────────────────────────

    public function test_any_user_can_list_branches()
    {
        $response = $this->actingAsWithCompany($this->regularUser)
            ->getJson('/api/v1/branches');

        $response->assertOk();
    }

    public function test_any_user_can_list_departments()
    {
        $response = $this->actingAsWithCompany($this->regularUser)
            ->getJson('/api/v1/departments');

        $response->assertOk();
    }

    // ── POST /public-holidays ─────────────────────────────────────────────────

    public function test_system_administrator_can_create_public_holiday()
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->postJson('/api/v1/public-holidays', [
                'date' => '2026-12-09',
                'name' => 'Independence Day',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Independence Day');
    }

    public function test_unauthenticated_requests_are_rejected()
    {
        $response = $this->getJson('/api/v1/company');
        $response->assertUnauthorized();
    }
}
