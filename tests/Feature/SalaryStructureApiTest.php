<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalaryStructureApiTest extends TestCase
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

    public function test_can_create_salary_structure(): void
    {
        $response = $this->actingAsWithCompany($this->hrManager)
            ->postJson('/api/v1/salary-structures', [
                'code' => 'GRADE-1',
                'name' => 'Grade 1',
                'currency' => 'TZS',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'GRADE-1');

        $this->assertDatabaseHas('salary_structures', [
            'code' => 'GRADE-1',
        ]);
    }
}
