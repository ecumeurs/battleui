<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @spec-link [[api_auth_register]]
     * @spec-link [[api_standard_envelope]]
     */
    public function test_user_can_register()
    {
        $payload = [
            'account_name' => 'SurvivorX',
            'email' => 'survivor@example.com',
            'password' => 'VeryLongPassword123!',
            'password_confirmation' => 'VeryLongPassword123!',
            'full_address' => 'Wasteland Sector 7, Grid 44',
            'birth_date' => '1990-01-01',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

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

        $this->assertTrue($response->json('success'));
        $this->assertEquals('SurvivorX', $response->json('data.user.account_name'));
        
        // Verify 3 characters were created
        $user = User::where('email', 'survivor@example.com')->first();
        $this->assertCount(3, $user->characters);
    }

    /**
     * @spec-link [[api_auth_login]]
     * @spec-link [[api_standard_envelope]]
     */
    public function test_user_can_login_with_account_name()
    {
        $user = User::factory()->create([
            'account_name' => 'SurvivorX',
            'password_hash' => \Hash::make('VeryLongPassword123!'),
        ]);

        $payload = [
            'account_name' => 'SurvivorX',
            'password' => 'VeryLongPassword123!',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertNotNull($response->json('data.token'));
    }

    public function test_registration_validation_fails_for_short_password()
    {
        $payload = [
            'account_name' => 'SurvivorX',
            'email' => 'survivor@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'full_address' => 'Address',
            'birth_date' => '1990-01-01',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }
}
