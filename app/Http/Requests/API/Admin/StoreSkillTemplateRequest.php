<?php

namespace App\Http\Requests\API\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @spec-link [[api_skill_template_admin_crud]]
 * @spec-link [[rule_admin_content_authority]]
 */
class StoreSkillTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:100',
            'behavior'        => 'required|string|in:Direct,Reaction,Passive,Counter,Trap',
            'targeting'       => 'required|array',
            'costs'           => 'required|array',
            'effect'          => 'required|array',
            'grade'           => 'required|string|in:I,II,III,IV,V',
            'weight_positive' => 'required|integer|min:0',
            'weight_negative' => 'required|integer|min:0',
            'available'       => 'boolean',
            'version'         => 'string|max:10',
        ];
    }
}
