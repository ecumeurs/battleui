<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed skill design library.
 *
 * @spec-link [[entity_skill_template]]
 * @spec-link [[rule_admin_content_authority]]
 */
class SkillTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'behavior',
        'targeting',
        'costs',
        'effect',
        'grade',
        'weight_positive',
        'weight_negative',
        'available',
        'version',
    ];

    protected $casts = [
        'targeting'       => 'array',
        'costs'           => 'array',
        'effect'          => 'array',
        'available'       => 'bool',
        'weight_positive' => 'integer',
        'weight_negative' => 'integer',
    ];

    public function characterSkills()
    {
        return $this->hasMany(CharacterSkill::class, 'skill_template_id');
    }
}
