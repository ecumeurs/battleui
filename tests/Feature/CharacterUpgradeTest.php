<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @test-link [[upsilonapi:api_profile_character]]
 * @test-link [[shared:rule_progression]]
 * @test-link [[shared:rule_stat_taxonomy]]
 */
class CharacterUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_upgrade_respects_cp_cap()
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
            'hp' => 30, 'attack' => 10, 'defense' => 5, 'movement' => 3,
            'initial_movement' => 3,
            'spent_cp' => 0
        ]);

        // Attempting to spend 105 CP (e.g., 21 Attack * 5 CP) with 0 wins (max 100 CP) should fail
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => ['attack' => 21]
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_character_upgrade_allows_increase_within_cp_cap()
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
            'hp' => 30, 'attack' => 10, 'defense' => 5, 'movement' => 3,
            'initial_movement' => 3,
            'spent_cp' => 0
        ]);

        // Total cap is 100 + (2 * 10) = 120. 
        // Adding 4 Movement (4 * 30 CP = 120 CP) should succeed exactly.
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => ['movement' => 4]
        ]);

        $response->assertStatus(200);
        $this->assertEquals(7, $character->fresh()->movement);
        $this->assertEquals(120, $character->fresh()->spent_cp);
    }

    public function test_character_upgrade_accumulates_spent_cp()
    {
        $user = User::create([
            'account_name' => 'testuser3',
            'email' => 'test3@example.com',
            'password_hash' => 'hash',
            'total_wins' => 5
        ]);

        $character = Character::create([
            'player_id' => $user->id,
            'name' => 'Test Character',
            'hp' => 30, 'attack' => 10, 'defense' => 5, 'movement' => 3,
            'initial_movement' => 3,
            'spent_cp' => 50
        ]);

        // Max CP: 100 + 50 = 150. Already spent 50. Remaining: 100.
        // Spend 90 CP: HP +10 (10), Attack +10 (50), Defense +6 (30) = 90 CP.
        $response = $this->actingAs($user)->postJson("/api/v1/profile/character/{$character->id}/upgrade", [
            'stats' => [
                'hp' => 10,
                'attack' => 10,
                'defense' => 6
            ]
        ]);

        $response->assertStatus(200);
        $freshChar = $character->fresh();
        $this->assertEquals(40, $freshChar->hp);
        $this->assertEquals(20, $freshChar->attack);
        $this->assertEquals(11, $freshChar->defense);
        $this->assertEquals(140, $freshChar->spent_cp);
    }
}
