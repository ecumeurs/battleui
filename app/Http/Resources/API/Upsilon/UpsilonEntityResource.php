<?php

namespace App\Http\Resources\API\Upsilon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hp' => $this->hp,
            'maxHp' => $this->hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'move' => $this->movement,
            'maxMove' => $this->movement,
            'position' => $this->position ?? null,
        ];
    }
}
