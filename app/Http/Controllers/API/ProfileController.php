<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Character;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * @spec-link [[api_profile_character]]
     */
    public function getProfile(Request $request, string $id)
    {
        $user = User::with('characters')->findOrFail($id);

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Profile retrieved.',
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function updateProfile(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'account_name' => 'string|max:255|unique:users,account_name,' . $id,
            'email' => 'string|email|max:255|unique:users,email,' . $id,
        ]);

        $user->update($validated);

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Profile updated.',
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function getCharacter(Request $request, string $id, string $characterId)
    {
        $character = Character::where('player_id', $id)->findOrFail($characterId);

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Character retrieved.',
            'success' => true,
            'data' => $character,
        ]);
    }
    
    /**
     * @spec-link [[api_profile_character]]
     */
    public function getCharacters(Request $request, string $id)
    {
        $characters = Character::where('player_id', $id)->get();

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Characters retrieved.',
            'success' => true,
            'data' => $characters,
        ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[mech_character_reroll_limit]]
     */
    public function rerollCharacter(Request $request, string $id, string $characterId)
    {
        $user = $request->user();
        if ($user->id !== $id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($user->reroll_count >= 3) {
            return response()->json([
                'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
                'message' => 'Reroll limit reached.',
                'success' => false,
                'data' => ['reroll_count' => $user->reroll_count],
            ], 403);
        }

        $character = Character::where('player_id', $id)->findOrFail($characterId);

        DB::transaction(function () use ($user, $character) {
            $character->rerollStats();
            $user->increment('reroll_count');
        });

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Character rerolled.',
            'success' => true,
            'data' => [
                'character' => $character->fresh(),
                'reroll_count' => $user->fresh()->reroll_count,
            ],
        ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     * @spec-link [[rule_progression]]
     */
    public function updateCharacter(Request $request, string $id, string $characterId)
    {
        $character = Character::where('player_id', $id)->with('player')->findOrFail($characterId);
        $user = $character->player;
        
        $validated = $request->validate([
            'stats' => 'required|array',
            'stats.hp' => 'integer|min:0',
            'stats.attack' => 'integer|min:0',
            'stats.defense' => 'integer|min:0',
            'stats.movement' => 'integer|min:0',
        ]);

        $newHp = $character->hp + ($validated['stats']['hp'] ?? 0);
        $newAttack = $character->attack + ($validated['stats']['attack'] ?? 0);
        $newDefense = $character->defense + ($validated['stats']['defense'] ?? 0);
        $newMovement = $character->movement + ($validated['stats']['movement'] ?? 0);

        // Constraint 1: Sum of all attributes <= 10 + total_wins
        $totalAttributes = $newHp + $newAttack + $newDefense + $newMovement;
        $maxAttributes = 10 + $user->total_wins;

        if ($totalAttributes > $maxAttributes) {
            return response()->json([
                'success' => false,
                'message' => "Upgrade failed: Total attributes ($totalAttributes) exceed the allowed cap ($maxAttributes based on {$user->total_wins} wins).",
            ], 400);
        }

        // Constraint 2: Movement <= initial_movement + floor(total_wins / 5)
        $maxMovement = $character->initial_movement + floor($user->total_wins / 5);
        if ($newMovement > $maxMovement) {
            return response()->json([
                'success' => false,
                'message' => "Upgrade failed: Movement ($newMovement) exceeds the allowed limit ($maxMovement based on {$user->total_wins} wins and initial movement {$character->initial_movement}).",
            ], 400);
        }

        // Constraint 3: No negative attributes (already partially covered by min:0 in validation if increments are positive)
        // If the API implies absolute values, we check them. If it's increments, we check sums.
        // The original code was `$character->$stat += $value;` so they are increments.
        if ($newHp < 0 || $newAttack < 0 || $newDefense < 0 || $newMovement < 0) {
             return response()->json([
                'success' => false,
                'message' => "Upgrade failed: Attributes cannot be negative.",
            ], 400);
        }

        $character->update([
            'hp' => $newHp,
            'attack' => $newAttack,
            'defense' => $newDefense,
            'movement' => $newMovement,
        ]);

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Character upgraded.',
            'success' => true,
            'data' => $character,
        ]);
    }

    /**
     * @spec-link [[api_profile_character]]
     */
    public function deleteCharacter(Request $request, string $id, string $characterId)
    {
        $character = Character::where('player_id', $id)->findOrFail($characterId);
        $character->delete();

        return response()->json([
            'request_id' => $request->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'Character deleted.',
            'success' => true,
            'data' => null,
        ]);
    }
}