<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @spec-link [[api_arena_existence_check]]
 * @test-link [[upsilonapi:api_arena_existence_check]]
 */
class MatchVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected GameMatch $match;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'account_name' => 'TestPlayer',
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        Character::generateInitialRoster($this->user->id);

        $this->match = GameMatch::create([
            'game_mode' => '1v1_PVP',
            'started_at' => now(),
        ]);

        MatchParticipant::create([
            'match_id' => $this->match->id,
            'player_id' => $this->user->id,
            'team' => 1,
        ]);
    }

    public function test_status_returns_matched_if_arena_exists()
    {
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkArenaExistence')
                ->with($this->match->id)
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['exists' => true]
                ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/matchmaking/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'matched',
                    'match_id' => $this->match->id,
                ]
            ]);
        
        $this->assertNull($this->match->fresh()->concluded_at);
    }

    public function test_status_resolves_match_if_arena_missing()
    {
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkArenaExistence')
                ->with($this->match->id)
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => ['exists' => false]
                ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/matchmaking/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'idle',
                ]
            ]);
        
        $this->assertNotNull($this->match->fresh()->concluded_at);
        $this->assertNull($this->match->fresh()->winning_team_id);
    }

    public function test_status_resolves_match_if_engine_unreachable()
    {
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkArenaExistence')
                ->with($this->match->id)
                ->once()
                ->andThrow(new \App\Exceptions\EngineConnectionException("Connection refused"));
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/matchmaking/status');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'idle',
                ]
            ]);
        
        $this->assertNotNull($this->match->fresh()->concluded_at);
    }
}
