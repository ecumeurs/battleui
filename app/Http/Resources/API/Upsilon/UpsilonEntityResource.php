<?php

namespace App\Http\Resources\API\Upsilon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @spec-link [[entity_character]]
 */
class UpsilonEntityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this is the Character model or an array/object matching the structure
        /**
         * NOTE: This resource is used primarily to transmit baseline character data TOWARD the Upsilon engine.
         * The 'hp' field in the database represents a character's maximum potential (Max HP). 
         * Since current HP is a consumable stat managed during battle and not persisted in the database roster, 
         * we map both 'hp' and 'max_hp' to this baseline value for engine initialization.
         */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hp' => $this->hp,
            'max_hp' => $this->hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'move' => $this->movement,
            'max_move' => $this->movement,
            'position' => $this->position ?? null,
        ];
    }
}
