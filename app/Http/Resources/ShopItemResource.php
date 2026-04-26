<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @spec-link [[upsilonapi:api_shop_browse]]
 */
class ShopItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'type'       => $this->type,
            'slot'       => $this->slot,
            'properties' => $this->properties,
            'cost'       => (int) $this->cost,
            'available'  => (bool) $this->available,
        ];
    }
}
