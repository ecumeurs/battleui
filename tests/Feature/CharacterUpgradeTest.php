<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_upgrade_respects_total_wins_cap()
    {
        $user = User::create([
            'account_name' => 'testuser1',
            'email' => 'test1@example.com',
            'password_hash' => 'hash',
            'total_wins' => 0
        ]);
        
        $character = Character::create([
            'player_id' => $user->id,
            'name' => 'Test Character',
            'hp' => 3, 'attack' => 2, 'defense' => 2, 'movement' => 3, // Sum = 10
            'initial_movement' => 3
        ]);

        // Attempting to add 1 HP (Total 11) with 0 wins should fail
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => ['hp' => 1]
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_character_upgrade_allows_increase_within_wins_cap()
    {
        $user = User::create([
            'account_name' => 'testuser2',
            'email' => 'test2@example.com',
            'password_hash' => 'hash',
            'total_wins' => 2
        ]);

        $character = Character::create([
            'player_id' => $user->id,
            'name' => 'Test Character',
            'hp' => 3, 'attack' => 2, 'defense' => 2, 'movement' => 3, // Sum = 10
            'initial_movement' => 3
        ]);

        // Total cap is 10 + 2 = 12. Adding 2 points should succeed.
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => ['hp' => 2]
        ]);

        $response->assertStatus(200);
        $this->assertEquals(5, $character->fresh()->hp);
    }

    public function test_character_upgrade_respects_movement_throttle()
    {
        $user = User::create([
            'account_name' => 'testuser3',
            'email' => 'test3@example.com',
            'password_hash' => 'hash',
            'total_wins' => 4
        ]);

        $character = Character::create([
            'player_id' => $user->id,
            'name' => 'Test Character',
            'hp' => 3, 'attack' => 1, 'defense' => 1, 'movement' => 1, // Sum = 6
            'initial_movement' => 1
        ]);

        // Even if within total cap (14), movement upgrade should fail if < 5 wins
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => ['movement' => 1]
        ]);

        $response->assertStatus(400);
        
        // Success with 5 wins
        $user->update(['total_wins' => 5]);
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => ['movement' => 1]
        ]);
        $response->assertStatus(200);
        $this->assertEquals(2, $character->fresh()->movement);
    }
}
