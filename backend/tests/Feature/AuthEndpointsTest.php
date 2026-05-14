<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_endpoint_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'auth_case_user',
            'email' => 'auth_case_user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'username', 'email'],
            ])
            ->assertJsonPath('user.username', 'auth_case_user')
            ->assertJsonPath('user.email', 'auth_case_user@example.com');

        $this->assertDatabaseHas('users', [
            'username' => 'auth_case_user',
            'email' => 'auth_case_user@example.com',
        ]);
    }

    public function test_login_endpoint_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login_case@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login_case@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'username', 'email'],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'login_case@example.com');
    }

    public function test_user_can_login_after_registering(): void
    {
        $this->postJson('/api/auth/register', [
            'username' => 'round_trip_user',
            'email' => 'round_trip_user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->postJson('/api/auth/login', [
            'email' => 'round_trip_user@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('user.username', 'round_trip_user');
    }
}