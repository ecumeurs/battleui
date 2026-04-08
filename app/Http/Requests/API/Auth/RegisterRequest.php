<?php

namespace App\Http\Requests\API\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @spec-link [[api_auth_register]]
 * @spec-link [[rule_password_policy]]
 */
class RegisterRequest extends FormRequest
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
            'account_name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:15',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(15)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'full_address' => 'required|string|max:1000',
            'birth_date' => 'required|date|before:today',
        ];
    }
}
