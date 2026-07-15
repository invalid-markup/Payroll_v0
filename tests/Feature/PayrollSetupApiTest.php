<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollSetupApiTest extends TestCase
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

    public function test_can_create_earning_type(): void
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson('/api/v1/earning-types', [
                'code' => 'HOUSING',
                'name' => 'Housing Allowance',
                'type' => 'allowance',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'HOUSING');

        $this->assertDatabaseHas('earning_types', [
            'code' => 'HOUSING',
        ]);
    }

    public function test_can_create_deduction_type(): void
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson('/api/v1/deduction-types', [
                'code' => 'LOAN',
                'name' => 'Loan Repayment',
                'type' => 'deduction',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'LOAN');

        $this->assertDatabaseHas('deduction_types', [
            'code' => 'LOAN',
        ]);
    }
}
