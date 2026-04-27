<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-character skill inventory — snapshot model (ISS-073 D5).
 * instance_data is a frozen JSON copy of the skill at acquisition time.
 * skill_template_id is informational only; never re-read at battle time.
 *
 * @spec-link [[entity_character_skill_inventory]]
 * @spec-link [[rule_character_skill_slots]]
 */
class CharacterSkill extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'character_id',
        'skill_template_id',
        'source',
        'instance_data',
        'equipped',
        'acquired_at',
        'equipped_at',
    ];

    protected $casts = [
        'instance_data' => 'array',
        'equipped'      => 'bool',
        'acquired_at'   => 'datetime',
        'equipped_at'   => 'datetime',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function skillTemplate()
    {
        return $this->belongsTo(SkillTemplate::class, 'skill_template_id');
    }
}
