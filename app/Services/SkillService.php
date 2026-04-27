<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterSkill;
use Illuminate\Support\Facades\DB;

/**
 * Skill inventory operations: roll, equip, unequip.
 * All mutations are DB-transactional and enforce slot limits.
 *
 * @spec-link [[entity_character_skill_inventory]]
 * @spec-link [[rule_character_skill_slots]]
 */
class SkillService
{
    public function __construct(private readonly SkillGeneratorBridge $generator) {}

    /**
     * Acquire a new skill via the engine generator (source='roll').
     *
     * @return CharacterSkill
     * @throws SkillServiceException
     */
    public function acquire(Character $character): CharacterSkill
    {
        $skillData = $this->generator->generate();

        return DB::transaction(function () use ($character, $skillData) {
            return CharacterSkill::create([
                'character_id'      => $character->id,
                'skill_template_id' => null,
                'source'            => 'roll',
                'instance_data'     => $skillData,
                'equipped'          => false,
                'acquired_at'       => now(),
            ]);
        });
    }

    /**
     * Equip a skill — enforces slot cap atomically.
     *
     * @throws SkillServiceException
     */
    public function equip(Character $character, CharacterSkill $skill): CharacterSkill
    {
        if ($skill->character_id !== $character->id) {
            throw new SkillServiceException(
                'Skill does not belong to this character.',
                403,
                SkillServiceException::ERR_SKILL_NOT_OWNED,
            );
        }

        if ($skill->equipped) {
            return $skill;
        }

        return DB::transaction(function () use ($character, $skill) {
            // lockForUpdate() is incompatible with COUNT(*) on PostgreSQL;
            // lock the rows first then count in PHP.
            $equippedCount = CharacterSkill::where('character_id', $character->id)
                ->where('equipped', true)
                ->lockForUpdate()
                ->get(['id'])
                ->count();

            // Always eager-load player so skill_slots accessor doesn't N+1.
            $character->loadMissing('player');
            $slots = $character->skill_slots;

            if ($equippedCount >= $slots) {
                throw new SkillServiceException(
                    "All {$slots} skill slot(s) are occupied.",
                    422,
                    SkillServiceException::ERR_SKILL_SLOT_FULL,
                );
            }

            $skill->equipped    = true;
            $skill->equipped_at = now();
            $skill->save();

            return $skill;
        });
    }

    /**
     * Unequip a skill.
     *
     * @throws SkillServiceException
     */
    public function unequip(Character $character, CharacterSkill $skill): CharacterSkill
    {
        if ($skill->character_id !== $character->id) {
            throw new SkillServiceException(
                'Skill does not belong to this character.',
                403,
                SkillServiceException::ERR_SKILL_NOT_OWNED,
            );
        }

        if (! $skill->equipped) {
            throw new SkillServiceException(
                'Skill is not currently equipped.',
                422,
                SkillServiceException::ERR_SKILL_NOT_EQUIPPED,
            );
        }

        return DB::transaction(function () use ($skill) {
            $skill->equipped    = false;
            $skill->equipped_at = null;
            $skill->save();

            return $skill;
        });
    }
}
