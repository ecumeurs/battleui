<?php

namespace Tests\Feature\API;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @spec-link [[api_auth_register]]
 * @spec-link [[api_auth_login]]
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @spec-link [[api_auth_register]]
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'account_name' => 'TestPlayer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], [
            'X-Request-ID' => (string) str()->uuid(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'request_id',
                'message',
                'success',
                'data' => [
                    'user' => ['id', 'account_name', 'email'],
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'account_name' => 'TestPlayer',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * @spec-link [[api_auth_login]]
     */
    public function test_user_can_login()
    {
        User::create([
            'account_name' => 'LoginPlayer',
            'email' => 'login@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'request_id',
                'message',
                'success',
                'data' => [
                    'user',
                    'token'
                ]
            ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::create([
            'account_name' => 'FailPlayer',
            'email' => 'fail@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'fail@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials.',
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::create([
            'account_name' => 'LogoutPlayer',
            'email' => 'logout@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out.',
            ]);

        $this->assertEmpty($user->tokens);
    }
}
