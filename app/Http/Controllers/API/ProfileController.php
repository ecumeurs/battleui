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
use App\Http\Requests\API\Profile\RenameCharacterRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\CharacterResource;

class ProfileController extends Controller
{
    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[customer_player_profile]]
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        return $this->success(new UserResource($user->load('characters')), 'Profile retrieved.');
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function getCredits(Request $request)
    {
        return $this->success([
            'credits' => $request->user()->credits,
        ], 'Credits retrieved.');
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
     * @spec-link [[mech_character_reroll_effect]]
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

        $incHp = $validated['stats']['hp'] ?? 0;
        $incAttack = $validated['stats']['attack'] ?? 0;
        $incDefense = $validated['stats']['defense'] ?? 0;
        $incMovement = $validated['stats']['movement'] ?? 0;

        $newHp = $character->hp + $incHp;
        $newAttack = $character->attack + $incAttack;
        $newDefense = $character->defense + $incDefense;
        $newMovement = $character->movement + $incMovement;

        // V2 Point-Buy CP Calculation
        $incrementCP = ($incHp * 1) + ($incAttack * 5) + ($incDefense * 5) + ($incMovement * 30);
        $newSpentCP = $character->spent_cp + $incrementCP;
        
        // Allowed CP: 100 base + (10 per win)
        $maxAllowedCP = 100 + ($user->total_wins * 10);

        if ($newSpentCP > $maxAllowedCP) {
            return $this->error("Upgrade failed: Total spent CP ($newSpentCP) exceeds the allowed cap ($maxAllowedCP based on {$user->total_wins} wins).", 400);
        }

        // Constraint: No negative attributes
        if ($newHp < 0 || $newAttack < 0 || $newDefense < 0 || $newMovement < 0) {
             return $this->error("Upgrade failed: Attributes cannot be negative.", 400);
        }

        $character->update([
            'hp' => $newHp,
            'attack' => $newAttack,
            'defense' => $newDefense,
            'movement' => $newMovement,
            'spent_cp' => $newSpentCP,
        ]);

        return $this->success(new CharacterResource($character), 'Character upgraded.');
    }

    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[rule_character_renaming]]
     */
    public function renameCharacter(RenameCharacterRequest $request, string $characterId)
    {
        $character = Character::findOrFail($characterId);
        $this->authorize('update', $character);

        $validated = $request->validated();

        $character->update([
            'name' => $validated['name'],
        ]);

        return $this->success(new CharacterResource($character), 'Character renamed.');
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