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
        
        // Ensure no one is left in queue
        $this->assertDatabaseCount('matchmaking_queues', 0);
    }
}
