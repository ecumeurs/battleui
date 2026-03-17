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
            'id' => $this->id,
            'name' => $this->name,
            'hp' => $this->hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'movement' => $this->movement,
            'initial_movement' => $this->initial_movement,
        ];
    }
}
