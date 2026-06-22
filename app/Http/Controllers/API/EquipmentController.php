<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Equipment\EquipItemRequest;
use App\Http\Resources\CharacterEquipmentResource;
use App\Models\Character;
use App\Models\CharacterEquipment;
use App\Models\PlayerInventory;
use App\Services\EquipmentService;
use App\Services\EquipmentServiceException;
use Illuminate\Http\Request;

/**
 * @spec-link [[api_equipment_management]]
 */
class EquipmentController extends Controller
{
    public function __construct(private readonly EquipmentService $equipment)
    {
    }

    public function show(Request $request, string $characterId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('view', $character);

        $equipment = CharacterEquipment::firstOrNew(['character_id' => $character->id]);
        if ($equipment->exists) {
            $equipment->load('armorItem.shopItem', 'utilityItem.shopItem', 'weaponItem.shopItem');
        }

        return $this->success(
            new CharacterEquipmentResource($equipment),
            'Equipment retrieved.',
        );
    }

    public function equip(EquipItemRequest $request, string $characterId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('equip', $character);

        $payload = $request->validated();
        $inventoryRow = PlayerInventory::with('shopItem')->find($payload['item_id']);
        if (! $inventoryRow) {
            return $this->error('Inventory item not found.', 404, null, ['reason' => 'inventory_not_found']);
        }

        // Verify the inventory row actually belongs to the requesting user
        // (defense-in-depth alongside ownership check inside the service).
        if ($inventoryRow->player_id !== $request->user()->id) {
            return $this->error('Inventory item does not belong to you.', 403, null, ['reason' => EquipmentService::ERR_INVENTORY_NOT_OWNED]);
        }

        try {
            $equipment = $this->equipment->equip($character, $inventoryRow);
        } catch (EquipmentServiceException $e) {
            return $this->error($e->getMessage(), $e->httpStatus(), null, ['reason' => $e->reason]);
        }

        return $this->success(
            new CharacterEquipmentResource($equipment),
            'Item equipped.',
        );
    }

    public function unequip(Request $request, string $characterId, string $slot)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('unequip', $character);

        if (! in_array($slot, ['armor', 'utility', 'weapon'], true)) {
            return $this->error("Unknown slot '{$slot}'.", 422, null, ['reason' => 'invalid_slot']);
        }

        try {
            $equipment = $this->equipment->unequip($character, $slot);
        } catch (EquipmentServiceException $e) {
            return $this->error($e->getMessage(), $e->httpStatus(), null, ['reason' => $e->reason]);
        }

        return $this->success(
            new CharacterEquipmentResource($equipment),
            'Item unequipped.',
        );
    }
}
