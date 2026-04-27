<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @spec-link [[entity_character_skill_inventory]]
 * @spec-link [[api_character_skill_inventory]]
 */
class CharacterSkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'character_id'      => $this->character_id,
            'skill_template_id' => $this->skill_template_id,
            'source'            => $this->source,
            'instance_data'     => $this->instance_data,
            'equipped'          => $this->equipped,
            'acquired_at'       => $this->acquired_at,
            'equipped_at'       => $this->equipped_at,
        ];
    }
}
