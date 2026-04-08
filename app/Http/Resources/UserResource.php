<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @spec-link [[requirement_customer_user_id_privacy]]
 */
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
            'account_name' => $this->account_name,
            'email' => $this->email,
            'full_address' => $this->full_address,
            'birth_date' => $this->birth_date ? $this->birth_date->toDateString() : null,
            'total_wins' => $this->total_wins,
            'total_losses' => $this->total_losses,
            'ratio' => $this->ratio,
            'reroll_count' => $this->reroll_count,
            'characters' => CharacterResource::collection($this->whenLoaded('characters')),
        ];
    }
}
