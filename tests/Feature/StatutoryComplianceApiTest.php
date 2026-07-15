<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatutoryComplianceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $sysAdmin;

    private User $payrollOfficer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->sysAdmin = User::factory()->create();
        $this->sysAdmin->assignRole('system_administrator');

        $this->payrollOfficer = User::factory()->create();
        $this->payrollOfficer->assignRole('payroll_officer');
    }

    private function actingAsUser(User $user): self
    {
        $token = $user->createToken('tenant', ['company:'.Str::uuid()->toString()]);

        return $this->withToken($token->plainTextToken);
    }

    // ── GET /statutory/configurations ─────────────────────────────────────────

    public function test_authorized_users_can_get_statutory_configurations()
    {
        $response = $this->actingAsUser($this->payrollOfficer)
            ->getJson('/api/v1/statutory/configurations');

        $response->assertOk();
    }

    // ── POST /statutory/configurations ────────────────────────────────────────

    public function test_system_administrator_can_create_statutory_configuration()
    {
        $response = $this->actingAsUser($this->sysAdmin)
            ->postJson('/api/v1/statutory/configurations', [
                'code' => 'wcf',
                'rate_percentage' => 0.6,
                'effective_from' => '2026-07-01',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'wcf')
            ->assertJsonPath('data.rate_percentage', 0.6);

        $this->assertDatabaseHas('statutory_configurations', [
            'code' => 'wcf',
            'rate_percentage' => 0.6,
            'effective_from' => '2026-07-01 00:00:00',
        ]);
    }

    public function test_payroll_officer_cannot_create_statutory_configuration()
    {
        $response = $this->actingAsUser($this->payrollOfficer)
            ->postJson('/api/v1/statutory/configurations', [
                'code' => 'wcf',
                'rate_percentage' => 0.6,
                'effective_from' => '2026-07-01',
            ]);

        $response->assertForbidden();
    }

    // ── GET /statutory/paye-brackets ─────────────────────────────────────────

    public function test_authorized_users_can_get_paye_brackets()
    {
        $response = $this->actingAsUser($this->payrollOfficer)
            ->getJson('/api/v1/statutory/paye-brackets');

        $response->assertOk();
    }

    // ── POST /statutory/paye-brackets/bulk ───────────────────────────────────

    public function test_system_administrator_can_create_bulk_paye_brackets()
    {
        $response = $this->actingAsUser($this->sysAdmin)
            ->postJson('/api/v1/statutory/paye-brackets/bulk', [
                'effective_from' => '2026-07-01',
                'brackets' => [
                    [
                        'minimum_income' => 0,
                        'maximum_income' => 270000,
                        'rate_percentage' => 0,
                        'base_tax_amount' => 0,
                    ],
                    [
                        'minimum_income' => 270000,
                        'maximum_income' => 520000,
                        'rate_percentage' => 8,
                        'base_tax_amount' => 0,
                    ],
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('paye_brackets', [
            'minimum_income' => 0,
            'effective_from' => '2026-07-01 00:00:00',
        ]);

        $this->assertDatabaseHas('paye_brackets', [
            'minimum_income' => 270000,
            'effective_from' => '2026-07-01 00:00:00',
        ]);
    }
}
