<?php

namespace Tests\Unit;

use App\Models\Character;
use Tests\TestCase;

class CharacterStatDistributionTest extends TestCase
{
    /**
     * @spec-link [[entity_character_allocate_hp]]
     */
    public function test_distribute_points_follows_base_stats_and_dispatch_rules()
    {
        for ($i = 0; $i < 100; $i++) {
            $stats = Character::distributePoints(10);
            
            // Check totals
            $this->assertEquals(10, array_sum($stats), "Total points must be exactly 10");
            
            // Check base minimums
            $this->assertGreaterThanOrEqual(3, $stats['hp'], "HP must be at least 3");
            $this->assertGreaterThanOrEqual(1, $stats['movement'], "Movement must be at least 1");
            $this->assertGreaterThanOrEqual(1, $stats['attack'], "Attack must be at least 1");
            $this->assertGreaterThanOrEqual(1, $stats['defense'], "Defense must be at least 1");
            
            // Check that exactly 4 points were dispatched (Base is 3+1+1+1 = 6)
            $dispatched = ($stats['hp'] - 3) + ($stats['movement'] - 1) + ($stats['attack'] - 1) + ($stats['defense'] - 1);
            $this->assertEquals(4, $dispatched, "Exactly 4 points must be dispatched among categories");
        }
    }
}
