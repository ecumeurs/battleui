<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

/**
 * @test-link [[mech_matchmaking]]
 * @test-link [[api_matchmaking]]
 */
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
                    'data' => [
                        'arena_id' => 'engine-123',
                        'initial_state' => [
                            'entities' => [],
                            'players' => []
                        ]
                    ]
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
     * AI entities carry auto_gen=true and a valid archetype so the Go engine generates stats
     * and behavior pipeline. Team-comp constraint (≤1 support, ≤1 sneak) is also enforced.
     *
     * @spec-link [[rule_archetype_grade_progression]]
     * @spec-link [[rule_team_composition]]
     */
    public function test_ai_entities_are_auto_gen_with_archetype()
    {
        $this->user->update(['total_wins' => 15]);

        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')
                ->once()
                ->withArgs(function ($matchId, $callbackUrl, $players) {
                    $aiPlayer = $players[1];

                    // Player carries total_wins for grade derivation on the Go side.
                    if (($aiPlayer->resource['total_wins'] ?? null) !== 15) return false;

                    $supportCount = 0;
                    $sneakCount = 0;
                    $validArchetypes = ['fighter', 'ranger', 'support', 'sneak'];
                    foreach ($aiPlayer->resource['entities'] as $entity) {
                        if (!($entity->auto_gen ?? false)) return false;
                        if (!in_array($entity->archetype ?? '', $validArchetypes, true)) return false;
                        if ($entity->archetype === 'support') $supportCount++;
                        if ($entity->archetype === 'sneak') $sneakCount++;
                    }
                    // Team-comp: ≤1 support, ≤1 sneak per AI team.
                    if ($supportCount > 1 || $sneakCount > 1) return false;
                    return true;
                })
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'initial_state' => ['entities' => [], 'players' => []]
                    ]
                ]);
        });

        $this->actingAs($this->user)
            ->postJson('/api/v1/matchmaking/join', [
                'character_ids' => $this->chars,
                'game_mode' => '1v1_PVE',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'matched');
    }
}
