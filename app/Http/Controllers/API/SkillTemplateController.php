<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillTemplateResource;
use App\Models\SkillTemplate;

/**
 * @spec-link [[api_skill_template_browse]]
 * @spec-link [[entity_skill_template]]
 */
class SkillTemplateController extends Controller
{
    /**
     * List all player-visible skill templates.
     */
    public function index()
    {
        $templates = SkillTemplate::where('available', true)->get();

        return $this->success(
            SkillTemplateResource::collection($templates),
            'Skill templates retrieved.',
        );
    }

    /**
     * Show a single skill template.
     */
    public function show(string $id)
    {
        $template = SkillTemplate::where('available', true)->findOrFail($id);

        return $this->success(
            new SkillTemplateResource($template),
            'Skill template retrieved.',
        );
    }
}
