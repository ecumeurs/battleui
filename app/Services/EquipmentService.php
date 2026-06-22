<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterEquipment;
use App\Models\PlayerInventory;
use Illuminate\Support\Facades\DB;

/**
 * Equip / unequip orchestration with cross-character mutual exclusivity.
 *
 * @spec-link [[api_equipment_management]]
 * @spec-link [[entity_character_equipment]]
 * @spec-link [[mechanic_three_slot_equipment_system]]
 */
class EquipmentService
{
    public const ERR_SLOT_MISMATCH        = 'slot_mismatch';
    public const ERR_INVENTORY_NOT_OWNED  = 'inventory_not_owned';
    public const ERR_SLOT_EMPTY           = 'slot_empty';

    /**
     * Equip a `PlayerInventory` row onto `$character`. The slot is inferred
     * from the underlying ShopItem's `slot`. Cross-character mutual
     * exclusivity is enforced atomically.
     *
     * @return CharacterEquipment fresh state.
     */
    public function equip(Character $character, PlayerInventory $inventoryRow): CharacterEquipment
    {
        // Ownership check: the inventory row must belong to the character's owner.
        if ($inventoryRow->player_id !== $character->player_id) {
            throw new EquipmentServiceException('Inventory item not owned by character\'s player.', 403, self::ERR_INVENTORY_NOT_OWNED);
        }

        $shopItem = $inventoryRow->shopItem;
        if (! $shopItem) {
            throw new EquipmentServiceException('Inventory row missing catalog reference.', 422, self::ERR_SLOT_MISMATCH);
        }

        $slotColumn = CharacterEquipment::slotColumn($shopItem->slot);

        return DB::transaction(function () use ($character, $inventoryRow, $slotColumn) {
            // Detach this inventory row from any other character of the same owner
            // (cross-character mutual exclusivity).
            $sameOwnerCharIds = Character::where('player_id', $character->player_id)
                ->where('id', '!=', $character->id)
                ->pluck('id');

            CharacterEquipment::whereIn('character_id', $sameOwnerCharIds)
                ->where(function ($q) use ($inventoryRow) {
                    $q->where('armor_item_id', $inventoryRow->id)
                      ->orWhere('utility_item_id', $inventoryRow->id)
                      ->orWhere('weapon_item_id', $inventoryRow->id);
                })
                ->each(function (CharacterEquipment $other) use ($inventoryRow) {
                    foreach (['armor_item_id', 'utility_item_id', 'weapon_item_id'] as $col) {
                        if ($other->{$col} === $inventoryRow->id) {
                            $other->{$col} = null;
                        }
                    }
                    $other->save();
                });

            // Upsert the target character's equipment row, set the slot.
            $equipment = CharacterEquipment::firstOrCreate(
                ['character_id' => $character->id],
            );
            $equipment->{$slotColumn} = $inventoryRow->id;
            $equipment->save();

            return $equipment->fresh()->load('armorItem.shopItem', 'utilityItem.shopItem', 'weaponItem.shopItem');
        });
    }

    public function unequip(Character $character, string $slot): CharacterEquipment
    {
        $slotColumn = CharacterEquipment::slotColumn($slot);

        $equipment = CharacterEquipment::where('character_id', $character->id)->first();
        if (! $equipment || $equipment->{$slotColumn} === null) {
            throw new EquipmentServiceException("Slot '{$slot}' is empty.", 404, self::ERR_SLOT_EMPTY);
        }

        $equipment->{$slotColumn} = null;
        $equipment->save();

        return $equipment->fresh()->load('armorItem.shopItem', 'utilityItem.shopItem', 'weaponItem.shopItem');
    }
}
