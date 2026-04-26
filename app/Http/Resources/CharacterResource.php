<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            // Class A — CP-upgradable, persisted [[shared:rule_progression]]
            'hp'               => (int) $this->hp,
            'mp'               => (int) $this->mp,
            'sp'               => (int) $this->sp,
            'attack'           => (int) $this->attack,
            'defense'          => (int) $this->defense,
            'movement'         => (int) $this->movement,
            'jump_height'      => (int) $this->jump_height,
            'crit_chance'      => (int) $this->crit_chance,
            'crit_damage'      => (int) $this->crit_damage,
            'initial_movement' => (int) $this->initial_movement,
            'spent_cp'         => (int) $this->spent_cp,
            // Equipment summary (when loaded)
            'equipment'        => $this->whenLoaded('equipment', function () {
                return new CharacterEquipmentResource($this->equipment);
            }),
        ];
    }
}
