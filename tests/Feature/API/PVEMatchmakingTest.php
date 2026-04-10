<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class PVEMatchmakingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $chars;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'account_name' => 'PVETester',
            'email' => 'pve@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        Character::generateInitialRoster($this->user->id);
        $this->chars = Character::where('player_id', $this->user->id)->pluck('id')->toArray();
    }

    /**
     * @spec-link [[mech_matchmaking]]
     */
    public function test_user_can_start_pve_match_instantly()
    {
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Arena started',
                    'data' => ['arena_id' => 'engine-123']
                ]);
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/matchmaking/join', [
                'character_ids' => $this->chars,
                'game_mode' => '1v1_PVE',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'matched'
                ]
            ]);

        $this->assertDatabaseHas('game_matches', [
            'game_mode' => '1v1_PVE'
        ]);
        
        // Assert AI participant has NULL player_id
        $this->assertDatabaseHas('match_participants', [
            'player_id' => null,
            'team' => 2
        ]);
        
        // Ensure no one is left in queue
        $this->assertDatabaseCount('matchmaking_queues', 0);
    }

    /**
     * @spec-link [[mech_matchmaking]]
     */
    public function test_2v2_pve_waits_for_second_player()
    {
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')->never();
        });

        // 1st player joins
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/matchmaking/join', [
                'character_ids' => $this->chars,
                'game_mode' => '2v2_PVE',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'queued');

        $this->assertDatabaseCount('matchmaking_queues', 1);
        $this->assertDatabaseCount('game_matches', 0);
    }

    /**
     * @spec-link [[rule_pve_winnability_balance]]
     */
    public function test_ai_defense_is_capped_by_player_attack()
    {
        // Force player characters to have low attack (e.g. 1)
        Character::where('player_id', $this->user->id)->update(['attack' => 2]);
        
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')
                ->once()
                ->withArgs(function ($matchId, $callbackUrl, $players) {
                    // Check AI player (item 1 in players list)
                    $aiPlayer = $players[1];
                    foreach ($aiPlayer->resource['entities'] as $entity) {
                        // AI Defense must be < Player Max Attack (2)
                        if ($entity->defense >= 2) return false;
                    }
                    return true;
                })
                ->andReturn(['success' => true]);
        });

        $this->actingAs($this->user)
            ->postJson('/api/v1/matchmaking/join', [
                'character_ids' => $this->chars,
                'game_mode' => '1v1_PVE',
            ]);
    }
}
