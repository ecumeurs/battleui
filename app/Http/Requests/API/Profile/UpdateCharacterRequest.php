<?php

namespace App\Http\Requests\API\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stats' => 'required|array',
            'stats.hp' => 'integer|min:0',
            'stats.attack' => 'integer|min:0',
            'stats.defense' => 'integer|min:0',
            'stats.movement' => 'integer|min:0',
        ];
    }
}
