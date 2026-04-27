<?php

namespace Tests\Feature\API;

use App\Models\Character;
use App\Models\CharacterSkill;
use App\Models\SkillTemplate;
use App\Models\User;
use App\Services\SkillGeneratorBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @test-link [[api_character_skill_inventory]]
 * @test-link [[rule_character_skill_slots]]
 * @test-link [[api_skill_template_browse]]
 */
class SkillTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['total_wins' => 0]);
        $this->character = Character::factory()->create(['player_id' => $this->user->id]);
        // Ensure skill_slots accessor can resolve player
        $this->character->setRelation('player', $this->user);
    }

    // ── Skill Template Browse ─────────────────────────────────────────────

    public function test_can_list_available_skill_templates()
    {
        SkillTemplate::factory()->count(3)->create(['available' => true]);
        SkillTemplate::factory()->count(2)->unavailable()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/skills/templates');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'behavior', 'grade', 'available'],
                ],
            ]);
    }

    public function test_can_show_single_available_skill_template()
    {
        $template = SkillTemplate::factory()->create(['available' => true]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/skills/templates/{$template->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $template->id);
    }

    public function test_unavailable_template_returns_404()
    {
        $template = SkillTemplate::factory()->unavailable()->create();

        $this->actingAs($this->user)
            ->getJson("/api/v1/skills/templates/{$template->id}")
            ->assertNotFound();
    }

    // ── Skill Inventory Index ─────────────────────────────────────────────

    public function test_can_list_character_skills()
    {
        CharacterSkill::factory()->count(2)->create(['character_id' => $this->character->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/profile/character/{$this->character->id}/skills");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_list_other_users_character_skills()
    {
        $other = User::factory()->create();
        $otherChar = Character::factory()->create(['player_id' => $other->id]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/profile/character/{$otherChar->id}/skills")
            ->assertForbidden();
    }

    // ── Roll (Acquire) ─────────────────────────────────────────────────────

    private function fakeEngineResponse(): void
    {
        Http::fake([
            '*/v1/skills/generate' => Http::response([
                'success' => true,
                'data'    => [
                    'id'              => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
                    'name'            => 'Test Skill',
                    'behavior'        => 'offensive',
                    'targeting'       => ['range' => ['value' => 1, 'max' => 3]],
                    'costs'           => ['mp' => ['value' => 2, 'max' => 10]],
                    'effect'          => ['damage' => 5],
                    'grade'           => 'C',
                    'weight_positive' => 50,
                    'weight_negative' => 10,
                ],
            ], 200),
        ]);
    }

    public function test_can_roll_a_skill()
    {
        $this->fakeEngineResponse();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/profile/character/{$this->character->id}/skills/roll");

        $response->assertStatus(201)
            ->assertJsonPath('data.source', 'roll')
            ->assertJsonPath('data.character_id', $this->character->id);

        $this->assertDatabaseHas('character_skills', [
            'character_id' => $this->character->id,
            'source'       => 'roll',
        ]);
    }

    public function test_roll_returns_503_when_engine_unreachable()
    {
        Http::fake([
            '*/v1/skills/generate' => Http::response([], 500),
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/profile/character/{$this->character->id}/skills/roll")
            ->assertStatus(503)
            ->assertJsonPath('meta.reason', 'ERR_GENERATOR_UNREACHABLE');
    }

    public function test_cannot_roll_skill_for_other_users_character()
    {
        $other = User::factory()->create();
        $otherChar = Character::factory()->create(['player_id' => $other->id]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/profile/character/{$otherChar->id}/skills/roll")
            ->assertForbidden();
    }

    // ── Equip ─────────────────────────────────────────────────────────────

    public function test_can_equip_a_skill()
    {
        $skill = CharacterSkill::factory()->create([
            'character_id' => $this->character->id,
            'equipped'     => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/profile/character/{$this->character->id}/skills/{$skill->id}/equip");

        $response->assertOk()
            ->assertJsonPath('data.equipped', true);

        $this->assertDatabaseHas('character_skills', [
            'id'       => $skill->id,
            'equipped' => true,
        ]);
    }

    public function test_equip_fails_when_all_slots_occupied()
    {
        // user has 0 wins → 1 slot; fill it
        CharacterSkill::factory()->equipped()->create(['character_id' => $this->character->id]);

        $skill = CharacterSkill::factory()->create([
            'character_id' => $this->character->id,
            'equipped'     => false,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/profile/character/{$this->character->id}/skills/{$skill->id}/equip")
            ->assertStatus(422)
            ->assertJsonPath('meta.reason', 'ERR_SKILL_SLOT_FULL');
    }

    public function test_equip_fails_for_skill_belonging_to_other_character()
    {
        $other = User::factory()->create();
        $otherChar = Character::factory()->create(['player_id' => $other->id]);
        $skill = CharacterSkill::factory()->create(['character_id' => $otherChar->id]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/profile/character/{$this->character->id}/skills/{$skill->id}/equip")
            ->assertStatus(403)
            ->assertJsonPath('meta.reason', 'ERR_SKILL_NOT_OWNED');
    }

    // ── Unequip ───────────────────────────────────────────────────────────

    public function test_can_unequip_a_skill()
    {
        $skill = CharacterSkill::factory()->equipped()->create([
            'character_id' => $this->character->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/profile/character/{$this->character->id}/skills/{$skill->id}/unequip");

        $response->assertOk()
            ->assertJsonPath('data.equipped', false);

        $this->assertDatabaseHas('character_skills', [
            'id'       => $skill->id,
            'equipped' => false,
        ]);
    }

    public function test_unequip_fails_when_skill_not_equipped()
    {
        $skill = CharacterSkill::factory()->create([
            'character_id' => $this->character->id,
            'equipped'     => false,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/profile/character/{$this->character->id}/skills/{$skill->id}/unequip")
            ->assertStatus(422)
            ->assertJsonPath('meta.reason', 'ERR_SKILL_NOT_EQUIPPED');
    }

    public function test_unequip_fails_for_skill_belonging_to_other_character()
    {
        $other = User::factory()->create();
        $otherChar = Character::factory()->create(['player_id' => $other->id]);
        $skill = CharacterSkill::factory()->equipped()->create(['character_id' => $otherChar->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/profile/character/{$this->character->id}/skills/{$skill->id}/unequip")
            ->assertStatus(403)
            ->assertJsonPath('meta.reason', 'ERR_SKILL_NOT_OWNED');
    }
}
