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
        $character = Character::with(['player'])->findOrFail($characterId);
        $this->authorize('view', $character);

        return $this->success(new CharacterResource($character), 'Character retrieved.');
    }
    
    /**
     * @spec-link [[api_profile_character]]
     */
    public function getCharacters(Request $request)
    {
        $user = $request->user();
        $characters = Character::with(['player'])->where('player_id', $user->id)->get();

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
     * @spec-link [[shared:rule_progression]]
     * @spec-link [[shared:rule_stat_taxonomy]]
     *
     * Class A stats only — Class B (AttackRange, Shield) cannot be CP-upgraded.
     */
    public function updateCharacter(UpdateCharacterRequest $request, string $characterId)
    {
        $user = $request->user();
        $character = Character::findOrFail($characterId);
        $this->authorize('update', $character);

        $validated = $request->validated();
        $stats = $validated['stats'];

        // Class A increments (default 0 if not provided).
        $inc = [
            'hp'          => $stats['hp']          ?? 0,
            'mp'          => $stats['mp']          ?? 0,
            'sp'          => $stats['sp']          ?? 0,
            'attack'      => $stats['attack']      ?? 0,
            'defense'     => $stats['defense']     ?? 0,
            'movement'    => $stats['movement']    ?? 0,
            'jump_height' => $stats['jump_height'] ?? 0,
            'crit_chance' => $stats['crit_chance'] ?? 0,
            'crit_damage' => $stats['crit_damage'] ?? 0,
        ];

        // CP costs per [[shared:rule_progression]] v2.1.
        $costs = [
            'hp'          => 1,
            'mp'          => 1,
            'sp'          => 1,
            'attack'      => 5,
            'defense'     => 5,
            'movement'    => 30,
            'jump_height' => 15,
            'crit_chance' => 10,
            'crit_damage' => 5,
        ];

        $incrementCP = 0;
        foreach ($inc as $stat => $delta) {
            $incrementCP += $delta * $costs[$stat];
        }

        $newSpentCP   = $character->spent_cp + $incrementCP;
        $maxAllowedCP = 100 + ($user->total_wins * 10);

        if ($newSpentCP > $maxAllowedCP) {
            return $this->error("Upgrade failed: Total spent CP ($newSpentCP) exceeds the allowed cap ($maxAllowedCP based on {$user->total_wins} wins).", 400);
        }

        // No-negative constraint on every Class A stat.
        $newValues = [];
        foreach ($inc as $stat => $delta) {
            $newValues[$stat] = $character->{$stat} + $delta;
            if ($newValues[$stat] < 0) {
                return $this->error("Upgrade failed: Attribute '{$stat}' cannot be negative.", 400);
            }
        }
        $newValues['spent_cp'] = $newSpentCP;

        $character->update($newValues);

        return $this->success(new CharacterResource($character->fresh()), 'Character upgraded.');
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