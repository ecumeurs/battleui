<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @spec-link [[api_profile_character]]
 * @spec-link [[mech_character_reroll]]
 */
class CharacterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::create([
            'account_name' => 'ProfilePlayer',
            'email' => 'profile@example.com',
            'password_hash' => bcrypt('password123'),
        ]);

        Character::generateInitialRoster($this->user->id);
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function test_user_can_get_profile_with_characters()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/profile/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'characters' => [
                        '*' => ['id', 'name', 'hp', 'attack', 'defense', 'movement']
                    ]
                ]
            ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[mech_character_reroll_limit]]
     */
    public function test_user_can_reroll_character_stats()
    {
        $character = $this->user->characters()->first();
        $oldStats = $character->only(['hp', 'attack', 'defense', 'movement']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/profile/{$this->user->id}/character/{$character->id}/reroll");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Character rerolled.',
            ]);

        $this->assertEquals(1, $this->user->fresh()->reroll_count);
    }

    public function test_user_cannot_reroll_past_limit()
    {
        $this->user->update(['reroll_count' => 3]);
        $character = $this->user->characters()->first();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/profile/{$this->user->id}/character/{$character->id}/reroll");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Reroll limit reached.',
            ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function test_user_can_upgrade_character_stats()
    {
        $character = $this->user->characters()->first();
        $initialAttack = $character->attack;

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/profile/{$this->user->id}/character/{$character->id}/upgrade", [
                'stats' => ['attack' => 2]
            ]);

        $response->assertStatus(200);
        $this->assertEquals($initialAttack + 2, $character->fresh()->attack);
    }
}
