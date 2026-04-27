<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @spec-link [[entity_skill_template]]
 * @spec-link [[api_skill_template_browse]]
 */
class SkillTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'behavior'        => $this->behavior,
            'targeting'       => $this->targeting,
            'costs'           => $this->costs,
            'effect'          => $this->effect,
            'grade'           => $this->grade,
            'weight_positive' => $this->weight_positive,
            'weight_negative' => $this->weight_negative,
            'available'       => $this->available,
            'version'         => $this->version,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
