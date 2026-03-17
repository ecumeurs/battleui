<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Character;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponder;
use App\Http\Requests\API\Profile\UpdateProfileRequest;
use App\Http\Requests\API\Profile\UpdateCharacterRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\CharacterResource;

class ProfileController extends Controller
{
    use ApiResponder;
    /**
     * @spec-link [[api_profile_character]]
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        return $this->success(new UserResource($user->load('characters')), 'Profile retrieved.');
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update($validated);

        return $this->success(new UserResource($user), 'Profile updated.');
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function getCharacter(Request $request, string $characterId)
    {
        $user = $request->user();
        $character = Character::findOrFail($characterId);
        $this->authorize('view', $character);

        return $this->success(new CharacterResource($character), 'Character retrieved.');
    }
    
    /**
     * @spec-link [[api_profile_character]]
     */
    public function getCharacters(Request $request)
    {
        $user = $request->user();
        $characters = Character::where('player_id', $user->id)->get();

        return $this->success(CharacterResource::collection($characters), 'Characters retrieved.');
    }

    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[mech_character_reroll_limit]]
     */
    public function rerollCharacter(Request $request, string $characterId)
    {
        $user = $request->user();

        $character = Character::findOrFail($characterId);
        $this->authorize('reroll', $character);

        DB::transaction(function () use ($user, $character) {
            $character->rerollStats();
            $user->increment('reroll_count');
        });

        return $this->success([
            'character' => new CharacterResource($character->fresh()),
            'reroll_count' => $user->fresh()->reroll_count,
        ], 'Character rerolled.');
    }

    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[rule_progression]]
     */
    public function updateCharacter(UpdateCharacterRequest $request, string $characterId)
    {
        $user = $request->user();
        $character = Character::findOrFail($characterId);
        $this->authorize('update', $character);
        
        $validated = $request->validated();

        $newHp = $character->hp + ($validated['stats']['hp'] ?? 0);
        $newAttack = $character->attack + ($validated['stats']['attack'] ?? 0);
        $newDefense = $character->defense + ($validated['stats']['defense'] ?? 0);
        $newMovement = $character->movement + ($validated['stats']['movement'] ?? 0);

        // Constraint 1: Sum of all attributes <= 10 + total_wins
        $totalAttributes = $newHp + $newAttack + $newDefense + $newMovement;
        $maxAttributes = 10 + $user->total_wins;

        if ($totalAttributes > $maxAttributes) {
            return $this->error("Upgrade failed: Total attributes ($totalAttributes) exceed the allowed cap ($maxAttributes based on {$user->total_wins} wins).", 400);
        }

        // Constraint 2: Movement <= initial_movement + floor(total_wins / 5)
        $maxMovement = $character->initial_movement + floor($user->total_wins / 5);
        if ($newMovement > $maxMovement) {
            return $this->error("Upgrade failed: Movement ($newMovement) exceeds the allowed limit ($maxMovement based on {$user->total_wins} wins and initial movement {$character->initial_movement}).", 400);
        }

        // Constraint 3: No negative attributes (already partially covered by min:0 in validation if increments are positive)
        // If the API implies absolute values, we check them. If it's increments, we check sums.
        // The original code was `$character->$stat += $value;` so they are increments.
        if ($newHp < 0 || $newAttack < 0 || $newDefense < 0 || $newMovement < 0) {
             return $this->error("Upgrade failed: Attributes cannot be negative.", 400);
        }

        $character->update([
            'hp' => $newHp,
            'attack' => $newAttack,
            'defense' => $newDefense,
            'movement' => $newMovement,
        ]);

        return $this->success(new CharacterResource($character), 'Character upgraded.');
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function deleteCharacter(Request $request, string $characterId)
    {
        $user = $request->user();
        $character = Character::findOrFail($characterId);
        $this->authorize('delete', $character);
        $character->delete();

        return $this->success(null, 'Character deleted.');
    }
}