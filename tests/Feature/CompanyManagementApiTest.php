<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $sysAdmin;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->sysAdmin = User::factory()->create();
        $this->sysAdmin->assignRole('system_administrator');
    }

    private function actingAsWithCompany(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_can_get_or_create_company_profile(): void
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->putJson('/api/v1/company', [
                'name' => 'Acme Tanzania',
                'tin' => '123-456-789',
                'registration_number' => 'REG-001',
                'address' => 'Dar es Salaam',
                'working_days_per_month' => 21,
                'financial_year_start_month' => 1,
                'sdl_enabled' => true,
                'wcf_enabled' => true,
                'sdl_employee_threshold' => 4,
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'Acme Tanzania');

        $this->assertDatabaseHas('company_profile', [
            'company_name' => 'Acme Tanzania',
        ]);
    }

    public function test_can_create_branch(): void
    {
        $response = $this->actingAsWithCompany($this->sysAdmin)
            ->postJson('/api/v1/branches', [
                'code' => 'BR-001',
                'name' => 'Dar es Salaam Branch',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'BR-001');

        $this->assertDatabaseHas('branches', [
            'code' => 'BR-001',
        ]);
    }
}
