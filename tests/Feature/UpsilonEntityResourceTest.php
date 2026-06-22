<?php

namespace Tests\Feature;

use App\Http\Resources\API\Upsilon\UpsilonEntityResource;
use App\Models\Character;
use App\Models\CharacterEquipment;
use App\Models\PlayerInventory;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @test-link [[entity_character_equipment]]
 * @test-link [[mechanic_three_slot_equipment_system]]
 * @test-link [[api_equipment_management]]
 */
class UpsilonEntityResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_includes_equipped_items()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['player_id' => $user->id]);
        
        $shopItem = ShopItem::create([
            'name' => 'Steel Plate',
            'type' => 'armor',
            'slot' => 'armor',
            'properties' => ['ArmorRating' => 10],
            'cost' => 500,
            'available' => true,
        ]);

        $inventoryItem = PlayerInventory::create([
            'player_id' => $user->id,
            'shop_item_id' => $shopItem->id,
            'quantity' => 1,
        ]);

        CharacterEquipment::create([
            'character_id' => $character->id,
            'armor_item_id' => $inventoryItem->id,
        ]);

        // Eager load as the resource expects
        $character->load([
            'equipment.armorItem.shopItem',
            'equipment.utilityItem.shopItem',
            'equipment.weaponItem.shopItem'
        ]);

        $resource = new UpsilonEntityResource($character);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('equipped_items', $data);
        $this->assertCount(1, $data['equipped_items']);
        $this->assertEquals($inventoryItem->id, $data['equipped_items'][0]['item_id']);
        $this->assertEquals('Steel Plate', $data['equipped_items'][0]['name']);
        $this->assertEquals(['ArmorRating' => 10], $data['equipped_items'][0]['properties']);
        $this->assertArrayHasKey('equipped_skills', $data);
        $this->assertEquals([], $data['equipped_skills']);
    }
}
