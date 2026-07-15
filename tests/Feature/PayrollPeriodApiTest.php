<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollPeriodApiTest extends TestCase
{
    use RefreshDatabase;

    private User $payrollOfficer;

    private string $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyId = Str::uuid()->toString();

        $this->payrollOfficer = User::factory()->create();
        $this->payrollOfficer->assignRole('payroll_officer');
    }

    private function actingAsWithCompany(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.$this->companyId]);

        return $this->withToken($token->plainTextToken);
    }

    public function test_can_create_payroll_period(): void
    {
        $response = $this->actingAsWithCompany($this->payrollOfficer)
            ->postJson('/api/v1/payroll-periods', [
                'name' => 'July 2026',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'July 2026');

        $this->assertDatabaseHas('payroll_periods', [
            'name' => 'July 2026',
        ]);
    }
}
