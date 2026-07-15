<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_switch_company()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $companyId = Str::uuid()->toString();

        // In MVP, switchCompany only requires a valid company UUID.
        // We simulate a valid company profile by bypassing validation if necessary, or just mocking.
        // Let's create a company profile first.
        DB::table('company_profile')->insert([
            'id' => Str::uuid()->toString(),
            'company_id' => $companyId,
            'company_name' => 'Test Co',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/switch-company', [
            'company_id' => $companyId,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['company_id', 'token']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        // Assert token was deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
