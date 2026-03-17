<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchmakingQueueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'game_mode' => $this->game_mode,
            'character_ids' => $this->character_ids,
            'queued_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
