<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use App\Models\MatchmakingQueue;
use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @spec-link [[api_matchmaking]]
 * @spec-link [[mech_matchmaking]]
 */
class MatchmakingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected array $chars1;
    protected array $chars2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::create([
            'account_name' => 'Player1',
            'email' => 'p1@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->user2 = User::create([
            'account_name' => 'Player2',
            'email' => 'p2@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        Character::generateInitialRoster($this->user1->id);
        Character::generateInitialRoster($this->user2->id);

        $this->chars1 = $this->user1->characters->pluck('id')->toArray();
        $this->chars2 = $this->user2->characters->pluck('id')->toArray();
    }

    public function test_user_can_join_matchmaking_queue()
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/v1/matchmaking/join', [
                'game_mode' => '1v1_PVP'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                    'expected_participants' => 2,
                    'empty_slots' => 1
                ]
            ]);
        
        $response->assertJsonMissing(['data' => ['user_id' => $this->user1->id]]);

        $this->assertDatabaseHas('matchmaking_queues', [
            'user_id' => $this->user1->id
        ]);
    }

    public function test_user_can_leave_matchmaking_queue()
    {
        MatchmakingQueue::create([
            'user_id' => $this->user1->id,
            'game_mode' => '1v1_PVP',
            'character_ids' => $this->chars1
        ]);

        $response = $this->actingAs($this->user1)
            ->deleteJson('/api/v1/matchmaking/leave');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('matchmaking_queues', [
            'user_id' => $this->user1->id
        ]);
    }

    public function test_match_is_triggered_when_two_players_join()
    {
        // Mock UpsilonApiService
        $spy = $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Arena started',
                    'data' => ['arena_id' => 'mock-arena-uuid']
                ]);
        });

        // Player 2 already in queue
        MatchmakingQueue::create([
            'user_id' => $this->user2->id,
            'game_mode' => '1v1_PVP',
            'character_ids' => $this->chars2
        ]);

        // Player 1 joins
        $response = $this->actingAs($this->user1)
            ->postJson('/api/v1/matchmaking/join', [
                'game_mode' => '1v1_PVP'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'matched',
                    'expected_participants' => 2,
                    'empty_slots' => 0
                ]
            ]);

        $this->assertDatabaseMissing('matchmaking_queues', ['user_id' => $this->user1->id]);
        $this->assertDatabaseMissing('matchmaking_queues', ['user_id' => $this->user2->id]);
        $this->assertDatabaseHas('game_matches', ['game_mode' => '1v1_PVP']);
    }

    public function test_user_can_poll_queue_status()
    {
        MatchmakingQueue::create([
            'user_id' => $this->user1->id,
            'game_mode' => '1v1_PVP',
            'character_ids' => $this->chars1
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/matchmaking/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                    'expected_participants' => 2
                ]
            ]);

        $response->assertJsonMissing(['data' => ['user_id' => $this->user1->id]]);
    }

    public function test_user_receives_idle_status_when_not_in_queue()
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/matchmaking/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'idle'
                ]
            ]);
    }

    public function test_user_can_poll_matched_status_after_matching()
    {
        // 1. Setup - Mock service
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')->once()->andReturn(['success' => true]);
        });

        // 2. Player 2 already in queue
        MatchmakingQueue::create([
            'user_id' => $this->user2->id,
            'game_mode' => '1v1_PVP',
            'character_ids' => $this->chars2
        ]);

        // 3. Player 1 joins
        $this->actingAs($this->user1)
            ->postJson('/api/v1/matchmaking/join', [
                'game_mode' => '1v1_PVP'
            ]);

        // 4. Poll status
        $response = $this->actingAs($this->user1)
            ->getJson('/api/v1/matchmaking/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'matched',
                    'expected_participants' => 2
                ]
            ])
            ->assertJsonStructure(['data' => ['match_id']]);
    }
}
