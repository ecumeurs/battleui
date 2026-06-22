<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a `player_inventory` row, joining the catalog details and (when
 * loaded via `withEquippedAnnotation` scope) the current equip binding.
 *
 * @spec-link [[api_inventory_list]]
 */
class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'shop_item'    => new ShopItemResource($this->whenLoaded('shopItem')),
            'quantity'     => (int) $this->quantity,
            'purchased_at' => optional($this->purchased_at)->toIso8601String(),
            'equipped_on'  => $this->equipped_on ?? null,
        ];
    }
}
