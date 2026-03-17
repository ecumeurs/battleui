<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'account_name' => $this->account_name,
            'email' => $this->email,
            'total_wins' => $this->total_wins,
            'total_losses' => $this->total_losses,
            'ratio' => $this->ratio,
            'reroll_count' => $this->reroll_count,
            'characters' => CharacterResource::collection($this->whenLoaded('characters')),
        ];
    }
}
