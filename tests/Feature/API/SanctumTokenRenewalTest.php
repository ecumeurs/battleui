<?php

namespace Tests\Feature\API;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SanctumTokenRenewalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'account_name' => 'RenewalPlayer',
            'email' => 'renewal@example.com',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $this->token = $this->user->createToken('test_token', expiresAt: now()->addMinutes(15))->plainTextToken;
    }

    /**
     * Test that a request within the first 10 minutes does NOT trigger a renewal.
     */
    public function test_no_renewal_before_10_minutes()
    {
        // Set time to 5 minutes after creation
        Carbon::setTestNow(now()->addMinutes(5));

        $response = $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonMissingPath('meta.token');
    }

    /**
     * Test that a request between 10 and 15 minutes trigger a renewal.
     */
    public function test_renewal_triggered_between_10_and_15_minutes()
    {
        // Set time to 11 minutes after creation
        Carbon::setTestNow(now()->addMinutes(11));

        $response = $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('meta.message', 'Token renewed')
            ->assertJsonStructure(['meta' => ['token']]);

        $newToken = $response->json('meta.token');
        $this->assertNotEquals($this->token, $newToken);

        // Verify old token now has a grace period (expires in ~20 seconds)
        $oldTokenRecord = $this->user->tokens()->where('name', 'test_token')->orderBy('id', 'asc')->first();
        $this->assertTrue($oldTokenRecord->expires_at->isFuture());
        $this->assertLessThanOrEqual(20, now()->diffInSeconds($oldTokenRecord->expires_at));
    }

    /**
     * Test that using the old token during the 20s grace period works but doesn't trigger another renewal.
     */
    public function test_grace_period_prevents_double_renewal()
    {
        // 1. Trigger initial renewal at 11m
        Carbon::setTestNow(now()->addMinutes(11));
        $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Clear auth state for next request
        Auth::forgetGuards();

        // 2. Make another request 5 seconds later using the SAME old token
        Carbon::setTestNow(now()->addSeconds(5));

        $response = $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonMissingPath('meta.token'); // Should NOT trigger again
    }

    /**
     * Test that the old token expires after the 20s grace period.
     */
    public function test_old_token_expires_after_grace_period()
    {
        // 1. Trigger initial renewal at 11m
        Carbon::setTestNow(now()->addMinutes(11));
        $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        // Clear auth state so Sanctum re-validates the token from DB
        Auth::forgetGuards();

        // 2. Wait 25 seconds (past the 20s grace period)
        Carbon::setTestNow(now()->addSeconds(25));

        $response = $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test that a token expires normally after 15 minutes if never renewed.
     */
    public function test_token_expires_after_15_minutes_inactivity()
    {
        // Wait 16 minutes
        Carbon::setTestNow(now()->addMinutes(16));

        $response = $this->getJson('/api/v1/matchmaking/status', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(401);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time warping
        parent::tearDown();
    }
}
