<?php

namespace App\Http\Requests\API\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @spec-link [[api_shop_item_admin_crud]]
 */
class UpdateShopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => 'sometimes|string|max:100',
            'type'              => 'sometimes|string|max:50',
            'slot'              => 'sometimes|string|in:armor,utility,weapon',
            'cost'              => 'sometimes|integer|min:0',
            'properties'        => 'sometimes|array',
            'available'         => 'sometimes|boolean',
            'skill_template_id' => 'sometimes|nullable|uuid|exists:skill_templates,id',
        ];
    }
}
