<?php

namespace App\Http\Requests\API\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @spec-link [[api_shop_item_admin_crud]]
 */
class StoreShopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:100',
            'type'              => 'sometimes|string|max:50',
            'slot'              => 'required|string|in:armor,utility,weapon',
            'cost'              => 'required|integer|min:0',
            'properties'        => 'required|array',
            'available'         => 'boolean',
            'skill_template_id' => 'nullable|uuid|exists:skill_templates,id',
        ];
    }
}
