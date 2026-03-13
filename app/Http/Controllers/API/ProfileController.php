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
     */
    public function updateCharacter(Request $request, string $id, string $characterId)
    {
        $character = Character::where('player_id', $id)->findOrFail($characterId);
        
        $validated = $request->validate([
            'stats' => 'required|array',
            'stats.hp' => 'integer|min:0',
            'stats.attack' => 'integer|min:0',
            'stats.defense' => 'integer|min:0',
            'stats.movement' => 'integer|min:0',
        ]);

        foreach ($validated['stats'] as $stat => $value) {
            $character->$stat += $value;
        }
        
        $character->save();

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