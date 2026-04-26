<?php

namespace App\Http\Requests\API\Shop;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseShopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_item_id' => 'required|uuid',
            'quantity'     => 'integer|min:1|max:99',
        ];
    }
}
