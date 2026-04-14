<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameMatchResource extends JsonResource
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
            'game_mode' => $this->game_mode,
            'started_at' => $this->started_at?->toIso8601String(),
            'concluded_at' => $this->concluded_at?->toIso8601String(),
            'winner_team_id' => $this->winning_team_id,
        ];
    }
}
