<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Character;
use App\Services\Contracts\UpsilonApiServiceInterface;
use App\Events\BoardUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @spec-link [[api_battle_proxy]]
 */
class BattleProxyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected GameMatch $match;
    protected Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'account_name' => 'BattlePlayer',
            'email' => 'battle@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $this->character = Character::create([
            'player_id' => $this->user->id,
            'name' => 'Hero',
            'hp' => 10,
            'movement' => 2,
            'attack' => 3,
            'defense' => 1,
            'initial_movement' => 2,
        ]);

        $this->match = GameMatch::create([
            'game_mode' => '1v1_PVP',
            'started_at' => now(),
        ]);

        \App\Models\MatchParticipant::create([
            'match_id' => $this->match->id,
            'player_id' => $this->user->id,
            'team' => 1,
        ]);
    }

    /**
     * @spec-link [[api_battle_proxy]]
     */
    public function test_action_is_proxied_to_go_engine()
    {
        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendAction')
                ->once()
                ->with($this->match->id, $this->user->id, $this->character->id, 'Move', [1, 1])
                ->andReturn([
                    'success' => true,
                    'message' => 'Action processed',
                    'data' => []
                ]);
        });

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/game/{$this->match->id}/action", [
                'player_id' => $this->user->id,
                'entity_id' => $this->character->id,
                'type' => 'Move',
                'target_coords' => [1, 1]
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * @spec-link [[api_battle_proxy]]
     */
    public function test_webhook_updates_state_and_broadcasts()
    {
        Event::fake();

        $payload = [
            'request_id' => 'engine-req-123',
            'message' => 'State update',
            'success' => true,
            'data' => [
                'match_id' => $this->match->id,
                'turn_counter' => 5,
                'board' => [['x' => 0, 'y' => 0, 'entity' => 'hero-id']]
            ]
        ];

        $response = $this->postJson('/api/webhook/upsilon', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('game_matches', [
            'id' => $this->match->id,
            'turn' => 5
        ]);

        $this->assertEquals(5, $this->match->fresh()->turn);

        Event::assertDispatched(BoardUpdated::class, function ($event) {
            return $event->match_id === $this->match->id && $event->data['turn_counter'] === 5;
        });
    }

    public function test_user_cannot_act_with_unowned_entity()
    {
        $otherUser = User::create([
            'account_name' => 'OtherPlayer',
            'email' => 'other@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $otherCharacter = Character::create([
            'player_id' => $otherUser->id,
            'name' => 'Enemy',
            'hp' => 10,
            'attack' => 5,
            'defense' => 2,
            'movement' => 2,
            'initial_movement' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/game/{$this->match->id}/action", [
                'entity_id' => $otherCharacter->id,
                'type' => 'Move',
                'target_coords' => [1, 1]
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_view_unauthorized_match()
    {
        $intruder = User::create([
            'account_name' => 'Intruder',
            'email' => 'spy@example.com',
            'password_hash' => bcrypt('password'),
        ]);

        $response = $this->actingAs($intruder)
            ->getJson("/api/v1/game/{$this->match->id}");

        $response->assertStatus(403);
    }
}
