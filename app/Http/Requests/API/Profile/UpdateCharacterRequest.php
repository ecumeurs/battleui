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
        // Class A only — Class B (AttackRange, Shield) intentionally absent
        // per [[shared:rule_stat_taxonomy]] (item / buff only).
        return [
            'stats'              => 'required|array',
            'stats.hp'           => 'integer|min:0',
            'stats.mp'           => 'integer|min:0',
            'stats.sp'           => 'integer|min:0',
            'stats.attack'       => 'integer|min:0',
            'stats.defense'      => 'integer|min:0',
            'stats.movement'     => 'integer|min:0',
            'stats.jump_height'  => 'integer|min:0',
            'stats.crit_chance'  => 'integer|min:0',
            'stats.crit_damage'  => 'integer|min:0',
        ];
    }
}
