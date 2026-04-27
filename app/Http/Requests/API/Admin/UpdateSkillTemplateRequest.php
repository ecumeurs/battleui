<?php

namespace App\Http\Requests\API\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @spec-link [[api_skill_template_admin_crud]]
 */
class UpdateSkillTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'sometimes|string|max:100',
            'behavior'        => 'sometimes|string|in:Direct,Reaction,Passive,Counter,Trap',
            'targeting'       => 'sometimes|array',
            'costs'           => 'sometimes|array',
            'effect'          => 'sometimes|array',
            'grade'           => 'sometimes|string|in:I,II,III,IV,V',
            'weight_positive' => 'sometimes|integer|min:0',
            'weight_negative' => 'sometimes|integer|min:0',
            'available'       => 'sometimes|boolean',
            'version'         => 'sometimes|string|max:10',
        ];
    }
}
