<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 3-slot view: each slot is either null or a populated InventoryItemResource.
 *
 * @spec-link [[upsilonapi:api_equipment_management]]
 */
class CharacterEquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'character_id' => $this->character_id,
            'armor'        => $this->armorItem
                ? new InventoryItemResource($this->armorItem->load('shopItem'))
                : null,
            'utility'      => $this->utilityItem
                ? new InventoryItemResource($this->utilityItem->load('shopItem'))
                : null,
            'weapon'       => $this->weaponItem
                ? new InventoryItemResource($this->weaponItem->load('shopItem'))
                : null,
        ];
    }
}
