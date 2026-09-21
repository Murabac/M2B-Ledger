<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user_payload(): void
    {
        $user = User::factory()->owner()->create([
            'email' => 'owner@example.test',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'owner@example.test',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'owner@example.test')
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.company_id', $user->company_id)
            ->assertJsonStructure(['token', 'token_type', 'user' => [
                'id', 'name', 'email', 'role', 'company_id', 'qb_sales_rep_name',
            ]]);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        User::factory()->create([
            'email' => 'owner@example.test',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/login', [
            'email' => 'owner@example.test',
            'password' => 'wrong',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Application auth guards persist between HTTP calls in the same test.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/status')
            ->assertUnauthorized();
    }

    public function test_authed_routes_require_sanctum_token(): void
    {
        $this->getJson('/api/status')->assertUnauthorized();
        $this->getJson('/api/accounts')->assertUnauthorized();
        $this->getJson('/api/customers')->assertUnauthorized();
        $this->getJson('/api/summary')->assertUnauthorized();
    }
}
