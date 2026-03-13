<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class ExtraMatchmakingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createUserWithChars($name)
    {
        $user = User::create([
            'account_name' => $name,
            'email' => $name . '@example.com',
            'password_hash' => bcrypt('password'),
        ]);
        Character::generateInitialRoster($user->id);
        return $user;
    }

    /**
     * @spec-link [[mech_matchmaking]]
     */
    public function test_2v2_pvp_requires_4_players()
    {
        $u1 = $this->createUserWithChars('u1');
        $u2 = $this->createUserWithChars('u2');
        $u3 = $this->createUserWithChars('u3');
        $u4 = $this->createUserWithChars('u4');

        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')->once()->andReturn(['success' => true]);
        });

        // Join 1
        $this->actingAs($u1)->postJson('/api/v1/matchmaking/join', ['game_mode' => '2v2_PVP'])
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.empty_slots', 3);

        // Join 2
        $this->actingAs($u2)->postJson('/api/v1/matchmaking/join', ['game_mode' => '2v2_PVP'])
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.empty_slots', 2);

        // Join 3
        $this->actingAs($u3)->postJson('/api/v1/matchmaking/join', ['game_mode' => '2v2_PVP'])
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.empty_slots', 1);

        // Join 4
        $this->actingAs($u4)->postJson('/api/v1/matchmaking/join', ['game_mode' => '2v2_PVP'])
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.empty_slots', 0);
    }

    /**
     * @spec-link [[mech_matchmaking]]
     */
    public function test_2v2_pve_requires_2_players()
    {
        $u1 = $this->createUserWithChars('pve1');
        $u2 = $this->createUserWithChars('pve2');

        $this->mock(UpsilonApiServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('startArena')->once()->andReturn(['success' => true]);
        });

        $this->actingAs($u1)->postJson('/api/v1/matchmaking/join', ['game_mode' => '2v2_PVE'])
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.empty_slots', 1);

        $this->actingAs($u2)->postJson('/api/v1/matchmaking/join', ['game_mode' => '2v2_PVE'])
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.empty_slots', 0);
    }
}
