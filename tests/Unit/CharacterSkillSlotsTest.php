<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @test-link [[rule_character_skill_slots]]
 */
class CharacterSkillSlotsTest extends TestCase
{
    use RefreshDatabase;

    private function characterWithWins(int $wins): Character
    {
        $user = User::factory()->create(['total_wins' => $wins]);
        $character = Character::factory()->create(['player_id' => $user->id]);
        $character->setRelation('player', $user);
        return $character;
    }

    public function test_zero_wins_gives_one_slot()
    {
        $this->assertEquals(1, $this->characterWithWins(0)->skill_slots);
    }

    public function test_nine_wins_still_gives_one_slot()
    {
        $this->assertEquals(1, $this->characterWithWins(9)->skill_slots);
    }

    public function test_ten_wins_gives_two_slots()
    {
        $this->assertEquals(2, $this->characterWithWins(10)->skill_slots);
    }

    public function test_nineteen_wins_gives_two_slots()
    {
        $this->assertEquals(2, $this->characterWithWins(19)->skill_slots);
    }

    public function test_forty_wins_gives_five_slots()
    {
        $this->assertEquals(5, $this->characterWithWins(40)->skill_slots);
    }

    public function test_forty_nine_wins_gives_five_slots()
    {
        $this->assertEquals(5, $this->characterWithWins(49)->skill_slots);
    }

    public function test_one_thousand_wins_is_capped_at_five_slots()
    {
        $this->assertEquals(5, $this->characterWithWins(1000)->skill_slots);
    }

    public function test_null_player_relation_defaults_to_zero_wins()
    {
        $character = new Character();
        $character->setRelation('player', new User(['total_wins' => null]));
        $this->assertEquals(1, $character->skill_slots);
    }
}
