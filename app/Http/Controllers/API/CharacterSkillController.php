<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CharacterSkillResource;
use App\Models\Character;
use App\Models\CharacterSkill;
use App\Services\SkillService;
use App\Services\SkillServiceException;
use Illuminate\Http\Request;

/**
 * @spec-link [[api_character_skill_inventory]]
 * @spec-link [[entity_character_skill_inventory]]
 */
class CharacterSkillController extends Controller
{
    public function __construct(private readonly SkillService $skills) {}

    /**
     * List all skills in this character's inventory.
     */
    public function index(Request $request, string $characterId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('view', $character);

        $skills = CharacterSkill::where('character_id', $character->id)
            ->orderByDesc('acquired_at')
            ->get();

        return $this->success(
            CharacterSkillResource::collection($skills),
            'Skills retrieved.',
        );
    }

    /**
     * Roll (acquire) a new skill for the character.
     *
     * @spec-link [[rule_character_skill_slots]]
     */
    public function roll(Request $request, string $characterId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('acquireSkill', $character);

        try {
            $skill = $this->skills->acquire($character);
        } catch (SkillServiceException $e) {
            return $this->error($e->getMessage(), $e->httpStatus(), null, ['reason' => $e->reason]);
        }

        if (! $character->roulette_used) {
            $character->update(['roulette_used' => true]);
        }

        return $this->success(
            new CharacterSkillResource($skill),
            'Skill acquired.',
            201,
        );
    }

    /**
     * Equip a skill into an active slot.
     *
     * @spec-link [[rule_character_skill_slots]]
     */
    public function equip(Request $request, string $characterId, string $skillId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('equipSkill', $character);

        $skill = CharacterSkill::findOrFail($skillId);

        try {
            $skill = $this->skills->equip($character, $skill);
        } catch (SkillServiceException $e) {
            return $this->error($e->getMessage(), $e->httpStatus(), null, ['reason' => $e->reason]);
        }

        return $this->success(
            new CharacterSkillResource($skill),
            'Skill equipped.',
        );
    }

    /**
     * Unequip a skill from its active slot.
     */
    public function unequip(Request $request, string $characterId, string $skillId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('unequipSkill', $character);

        $skill = CharacterSkill::findOrFail($skillId);

        try {
            $skill = $this->skills->unequip($character, $skill);
        } catch (SkillServiceException $e) {
            return $this->error($e->getMessage(), $e->httpStatus(), null, ['reason' => $e->reason]);
        }

        return $this->success(
            new CharacterSkillResource($skill),
            'Skill unequipped.',
        );
    }
}
