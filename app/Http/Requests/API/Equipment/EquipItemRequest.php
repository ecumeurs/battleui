<?php

namespace App\Http\Requests\API\Equipment;

use Illuminate\Foundation\Http\FormRequest;

class EquipItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|uuid',
        ];
    }
}
