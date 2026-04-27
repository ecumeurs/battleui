<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Admin\StoreSkillTemplateRequest;
use App\Http\Requests\API\Admin\UpdateSkillTemplateRequest;
use App\Http\Resources\SkillTemplateResource;
use App\Models\SkillTemplate;

/**
 * @spec-link [[api_skill_template_admin_crud]]
 * @spec-link [[rule_admin_content_authority]]
 */
class AdminSkillTemplateController extends Controller
{
    public function index()
    {
        $templates = SkillTemplate::orderBy('created_at', 'desc')->get();

        return $this->success(
            SkillTemplateResource::collection($templates),
            'Skill templates retrieved.',
        );
    }

    public function show(string $id)
    {
        $template = SkillTemplate::findOrFail($id);

        return $this->success(
            new SkillTemplateResource($template),
            'Skill template retrieved.',
        );
    }

    public function store(StoreSkillTemplateRequest $request)
    {
        $template = SkillTemplate::create($request->validated());

        return $this->success(
            new SkillTemplateResource($template),
            'Skill template created.',
            201,
        );
    }

    public function update(UpdateSkillTemplateRequest $request, string $id)
    {
        $template = SkillTemplate::findOrFail($id);
        $template->update($request->validated());

        return $this->success(
            new SkillTemplateResource($template->fresh()),
            'Skill template updated.',
        );
    }

    public function destroy(string $id)
    {
        $template = SkillTemplate::findOrFail($id);
        $template->delete();

        return $this->success(null, 'Skill template deleted.');
    }
}
