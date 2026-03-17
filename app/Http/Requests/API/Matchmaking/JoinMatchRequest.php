<?php

namespace App\Http\Requests\API\Matchmaking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinMatchRequest extends FormRequest
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
            'game_mode' => [
                'sometimes',
                'string',
                Rule::in(['1v1_PVP', '1v1_PVE', '2v2_PVP', '2v2_PVE']),
            ],
        ];
    }
}
