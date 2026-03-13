<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\UpsilonApiServiceInterface;
use App\DTOs\Upsilon\PlayerDTO;
use App\DTOs\Upsilon\EntityDTO;
use Illuminate\Support\Str;

/**
 * @spec-link [[api_go_battle_start]]
 * @spec-link [[mech_matchmaking]]
 */
class MatchMakingController extends Controller
{
    public function __construct(
        protected UpsilonApiServiceInterface $upsilonService
    ) {}

    private const MODE_CONFIG = [
        '1v1_PVP' => ['required' => 2, 'ai_count' => 0],
        '1v1_PVE' => ['required' => 1, 'ai_count' => 1],
        '2v2_PVP' => ['required' => 4, 'ai_count' => 0],
        '2v2_PVE' => ['required' => 2, 'ai_count' => 1], // 1 AI team
    ];

    public function joinMatch(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gameMode = $request->input('game_mode', '1v1_PVP');
        $config = self::MODE_CONFIG[$gameMode] ?? self::MODE_CONFIG['1v1_PVP'];

        // Pull user's characters (first 3)
        $characters = $user->characters()->take(3)->get();
        if ($characters->count() < 3) {
             // In a real scenario we might error, but for tests let's assume existence or generate if missing
             if ($characters->count() === 0) {
                 \App\Models\Character::generateInitialRoster($user->id);
                 $characters = $user->characters()->take(3)->get();
             }
        }

        $characterIds = $characters->pluck('id')->toArray();

        // Check if already in queue
        $queueEntry = \App\Models\MatchmakingQueue::where('user_id', $user->id)
            ->where('game_mode', $gameMode)
            ->first();

        if (!$queueEntry) {
            $queueEntry = \App\Models\MatchmakingQueue::create([
                'user_id' => $user->id,
                'game_mode' => $gameMode,
                'character_ids' => $characterIds
            ]);
        }

        // Check if threshold met
        $queue = \App\Models\MatchmakingQueue::where('game_mode', $gameMode)
            ->orderBy('created_at', 'asc')
            ->take($config['required'])
            ->get();

        if ($queue->count() >= $config['required']) {
            // Match found!
            $match = \App\Models\GameMatch::create([
                'game_mode' => $gameMode,
                'started_at' => now(),
            ]);

            $players = [];
            foreach ($queue as $index => $entry) {
                $entryUser = \App\Models\User::find($entry->user_id);
                $entryChars = \App\Models\Character::whereIn('id', $entry->character_ids)->get();
                
                $entities = [];
                foreach ($entryChars as $char) {
                    $entities[] = new EntityDTO(
                        id: $char->id,
                        name: $char->name,
                        hp: $char->hp,
                        maxHp: $char->hp,
                        attack: $char->attack,
                        defense: $char->defense,
                        move: $char->movement,
                        maxMove: $char->movement
                    );
                }

                // Assign teams: for 1v1_PVP, index 0 is team 1, index 1 is team 2.
                // For 2v2_PVP, 0,1 are team 1, 2,3 are team 2.
                $team = ($index < ($config['required'] / 2)) ? 1 : 2;
                if ($config['required'] === 1) $team = 1; // PVE

                $players[] = new PlayerDTO(
                    id: $entryUser->id,
                    team: $team,
                    ia: false,
                    entities: $entities
                );

                // Record participant
                \App\Models\MatchParticipant::create([
                    'match_id' => $match->id,
                    'player_id' => $entryUser->id,
                    'team' => $team,
                ]);
            }

            // Handle AI if needed
            if ($config['ai_count'] > 0) {
                // Simplified AI addition
                $players[] = new PlayerDTO(
                    id: Str::uuid()->toString(),
                    team: 2,
                    ia: true,
                    entities: [
                        new EntityDTO(
                            id: Str::uuid()->toString(),
                            name: "AI Unit 1",
                            hp: 10, maxHp: 10, attack: 3, defense: 2, move: 3, maxMove: 3
                        )
                    ]
                );
            }

            $this->upsilonService->startArena(
                $match->id,
                url('/api/webhook/upsilon'),
                $players
            );

            // Cleanup queue
            \App\Models\MatchmakingQueue::whereIn('id', $queue->pluck('id'))->delete();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'matched',
                    'match_id' => $match->id,
                    'expected_participants' => $config['required'],
                    'empty_slots' => 0
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'queued',
                'match_id' => null,
                'expected_participants' => $config['required'],
                'empty_slots' => $config['required'] - $queue->count()
            ]
        ]);
    }

    public function status(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // 1. Check if in queue
        $queueEntry = \App\Models\MatchmakingQueue::where('user_id', $user->id)->first();
        if ($queueEntry) {
            $config = self::MODE_CONFIG[$queueEntry->game_mode] ?? self::MODE_CONFIG['1v1_PVP'];
            $queueCount = \App\Models\MatchmakingQueue::where('game_mode', $queueEntry->game_mode)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                    'match_id' => null,
                    'expected_participants' => $config['required'],
                    'empty_slots' => max(0, $config['required'] - $queueCount),
                    'queued_at' => $queueEntry->created_at->toIso8601String()
                ]
            ]);
        }

        // 2. Check if in an active match (reconnection case)
        $participant = \App\Models\MatchParticipant::where('player_id', $user->id)
            ->latest()
            ->first();

        if ($participant && $participant->match && is_null($participant->match->concluded_at)) {
             $match = $participant->match;
             $config = self::MODE_CONFIG[$match->game_mode] ?? self::MODE_CONFIG['1v1_PVP'];

             return response()->json([
                 'success' => true,
                 'data' => [
                     'status' => 'matched',
                     'match_id' => $match->id,
                     'expected_participants' => $config['required'],
                     'empty_slots' => 0
                 ],
                 'message' => 'Match in progress. Reconnecting...'
             ]);
        }

        // 3. User is idle
        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'idle',
                'match_id' => null,
                'expected_participants' => null,
                'empty_slots' => null
            ],
            'message' => 'Not in queue'
        ]);
    }

    public function leaveMatch(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        \App\Models\MatchmakingQueue::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

}