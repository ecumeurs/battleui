<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CharacterSkillResource;
use App\Models\Character;
use App\Models\CharacterSkill;
use App\Services\SkillService;
use App\Services\SkillServiceException;
use Illuminate\Http\JsonResponse;
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
     * Accepts optional ?grade query param; enforces grade is within the character's win window.
     *
     * @spec-link [[rule_character_skill_slots]]
     * @spec-link [[shared:req_skill_generation]]
     */
    public function roll(Request $request, string $characterId): JsonResponse
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('acquireSkill', $character);

        $grade = $request->query('grade', 'I');

        $character->loadMissing('player');
        $wins = $character->player?->total_wins ?? 0;
        $allowed = $this->allowedGrades($wins);

        if (! in_array($grade, $allowed, true)) {
            return $this->error(
                "Grade {$grade} is not yet unlocked (requires more wins).",
                422,
                null,
                ['reason' => SkillServiceException::ERR_GRADE_OUT_OF_WINDOW],
            );
        }

        try {
            $skill = $this->skills->acquire($character, $grade);
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
     * Grades available based on total player wins.
     * I–II always available; III at 10+, IV at 20+, V at 30+.
     *
     * @return string[]
     */
    private function allowedGrades(int $wins): array
    {
        $grades = ['I', 'II'];
        if ($wins >= 10) {
            $grades[] = 'III';
        }
        if ($wins >= 20) {
            $grades[] = 'IV';
        }
        if ($wins >= 30) {
            $grades[] = 'V';
        }
        return $grades;
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
