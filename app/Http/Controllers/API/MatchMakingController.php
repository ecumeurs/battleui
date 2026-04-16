<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Support\Str;
use App\Traits\ApiResponder;
use App\Http\Requests\API\Matchmaking\JoinMatchRequest;
use App\Http\Resources\GameMatchResource;
use App\Http\Resources\API\Upsilon\UpsilonPlayerResource;
use App\Http\Resources\API\Upsilon\UpsilonEntityResource;

/**
 * @spec-link [[api_go_battle_start]]
 * @spec-link [[mech_matchmaking]]
 * @spec-link [[rule_matchmaking_single_queue]]
 * @spec-link [[api_websocket_game_events]]
 */
class MatchMakingController extends Controller
{
    use ApiResponder;
    public function __construct(
        protected UpsilonApiServiceInterface $upsilonService
    ) {}

    private const AI_NAME_SEGMENTS = [
        'prefixes' => ['Null_', 'Void_', 'DeathX', 'Rust_', 'Cyber', 'Neon', 'Ghost_', 'Cinder'],
        'subjects' => ['Vermin', 'Proxy', 'Ghost', 'Core', 'Code', 'Glitch', 'Zero', 'One'],
        'suffixes' => ['_X', 'v2', '_Bot', '_666', '_Alpha', '_Z'],
        'technicals' => ['Scrap', 'Static', 'Sludge', 'Terminal', 'Node', 'Array'],
        'abstracts' => ['Fracture', 'Desolation', 'Entropy', 'Echo']
    ];

    private function generateAIName(): string
    {
        $pattern = rand(0, 2);
        return match($pattern) {
            0 => self::AI_NAME_SEGMENTS['prefixes'][array_rand(self::AI_NAME_SEGMENTS['prefixes'])] . 
                 self::AI_NAME_SEGMENTS['subjects'][array_rand(self::AI_NAME_SEGMENTS['subjects'])] . 
                 self::AI_NAME_SEGMENTS['suffixes'][array_rand(self::AI_NAME_SEGMENTS['suffixes'])],
            1 => self::AI_NAME_SEGMENTS['technicals'][array_rand(self::AI_NAME_SEGMENTS['technicals'])] . '-' . rand(100, 999),
            default => self::AI_NAME_SEGMENTS['abstracts'][array_rand(self::AI_NAME_SEGMENTS['abstracts'])] . '_' . dechex(rand(4096, 65535))
        };
    }

    private const MODE_CONFIG = [
        '1v1_PVP' => ['required' => 2, 'ai_count' => 0],
        '1v1_PVE' => ['required' => 1, 'ai_count' => 1],
        '2v2_PVP' => ['required' => 4, 'ai_count' => 0],
        '2v2_PVE' => ['required' => 2, 'ai_count' => 1], // 1 AI team
    ];

    /**
     * @spec-link [[api_matchmaking]]
     */
    public function joinMatch(JoinMatchRequest $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
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

        /** @spec-link [[rule_matchmaking_single_queue]] */
        // Check if already in ANY queue
        $anyQueueEntry = \App\Models\MatchmakingQueue::where('user_id', $user->id)->first();
        if ($anyQueueEntry) {
            return $this->error('Conflict: You are already in a matchmaking queue.', 409);
        }

        // Check if in an active match
        $activeMatch = \App\Models\MatchParticipant::where('player_id', $user->id)
            ->whereHas('match', function($q) {
                $q->whereNull('concluded_at');
            })
            ->exists();
        
        if ($activeMatch) {
            return $this->error('Conflict: You are currently participating in an active match.', 409);
        }

        $queueEntry = \App\Models\MatchmakingQueue::create([
            'user_id' => $user->id,
            'game_mode' => $gameMode,
            'character_ids' => $characterIds
        ]);

        // Cleanup zombie entries where user was deleted
        $zombies = \App\Models\MatchmakingQueue::whereNotExists(function ($query) {
            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('users')
                  ->whereColumn('matchmaking_queues.user_id', 'users.id')
                  ->whereNull('users.deleted_at');
        })->delete();

        // Check if threshold met
        $queue = \App\Models\MatchmakingQueue::where('game_mode', $gameMode)
            ->orderBy('created_at', 'asc')
            ->take($config['required'])
            ->get();

        if ($queue->count() >= $config['required']) {
            // Match found!
            $match = \App\Models\GameMatch::create([
                'id' => (string) Str::uuid(),
                'game_mode' => $gameMode,
                'started_at' => now(),
            ]);

            $players = [];
            $participantIds = [];
            foreach ($queue as $index => $entry) {
                $entryUser = \App\Models\User::find($entry->user_id);
                $entryChars = \App\Models\Character::whereIn('id', $entry->character_ids)->get();
                
                // Assign teams: for 1v1_PVP, index 0 is team 1, index 1 is team 2.
                // For 2v2_PVP, 0,1 are team 1, 2,3 are team 2.
                $team = ($index < ($config['required'] / 2)) ? 1 : 2;
                if ($config['required'] === 1) $team = 1; // PVE

                $players[] = new UpsilonPlayerResource([
                    'id' => $entryUser->id,
                    'nickname' => $entryUser->account_name,
                    'team' => $team,
                    'ia' => false,
                    'entities' => $entryChars
                ]);

                // Record participant
                \App\Models\MatchParticipant::create([
                    'match_id' => $match->id,
                    'player_id' => $entryUser->id,
                    'team' => $team,
                ]);

                $participantIds[] = $entryUser->ws_channel_key;
            }

            // Handle AI if needed
            if ($config['ai_count'] > 0) {
                // Determine how many AI players we need. 
                // In 1v1_PVE, we need 1 AI (Team 2).
                // In 2v2_PVE, we need 2 AIs (Team 2).
                $aiPlayersToCreate = ($gameMode === '2v2_PVE') ? 2 : 1;

                // Calculate max human attack for balance rule [[rule_pve_winnability_balance]]
                $maxHumanAttack = 0;
                foreach ($players as $pResource) {
                    if (!$pResource->resource['ia']) {
                         $entityMax = collect($pResource->resource['entities'])->max('attack');
                         if ($entityMax > $maxHumanAttack) $maxHumanAttack = $entityMax;
                    }
                }

                for ($pIdx = 1; $pIdx <= $aiPlayersToCreate; $pIdx++) {
                    $aiPlayerId = "00000000-0000-0000-0000-00000000000{$pIdx}"; // Synthetic IDs AI_1, AI_2
                    $aiName = $this->generateAIName();

                    /** @spec-link [[rule_pve_winnability_balance]] */
                    $aiEntities = [];
                    for ($i = 0; $i < 3; $i++) {
                        $stats = \App\Models\Character::distributePoints(10);
                        // Cap defense if it exceeds player attack
                        if ($maxHumanAttack > 0 && $stats['defense'] >= $maxHumanAttack) {
                            $stats['defense'] = max(0, $maxHumanAttack - 1);
                        }

                        $aiEntities[] = (object)[
                            'id' => (string) Str::uuid(),
                            'name' => $this->generateAIName(), // Unique name per unit too
                            'hp' => $stats['hp'],
                            'max_hp' => $stats['hp'],
                            'attack' => $stats['attack'],
                            'defense' => $stats['defense'],
                            'movement' => $stats['movement'],
                            'max_movement' => $stats['movement']
                        ];
                    }

                    $players[] = new UpsilonPlayerResource([
                        'id' => $aiPlayerId,
                        'nickname' => $aiName,
                        'team' => 2,
                        'ia' => true,
                        'entities' => $aiEntities
                    ]);
                    
                    // Record AI participant with NULL player_id
                    \App\Models\MatchParticipant::create([
                        'match_id' => $match->id,
                        'player_id' => null, // AI refactored away from users table
                        'team' => 2,
                    ]);
                    
                    // We don't add to participantIds because we don't broadcast to AI
                }
            }

            $callbackUrl = config('services.upsilon.webhook_url') ?: str_replace('localhost', '127.0.0.1', url('/api/webhook/upsilon'));

            $arenaResponse = $this->upsilonService->startArena(
                $match->id,
                $callbackUrl,
                $players
            );

            // Synchronously cache the initial state to prevent race conditions in frontend load
            if (isset($arenaResponse['data']['initial_state'])) {
                $match->update([
                    'game_state_cache' => $arenaResponse['data']['initial_state']
                ]);
            }

            // Cleanup queue
            \App\Models\MatchmakingQueue::whereIn('id', $queue->pluck('id'))->delete();

            // Broadcast to participants
            foreach ($participantIds as $channelKey) {
                broadcast(new \App\Events\MatchFound($channelKey, $match->id));
            }

            return $this->success([
                'status' => 'matched',
                'match_id' => $match->id,
                'expected_participants' => $config['required'],
                'empty_slots' => 0
            ]);
        }

        return $this->success([
            'status' => 'queued',
            'match_id' => null,
            'expected_participants' => $config['required'],
            'empty_slots' => $config['required'] - $queue->count()
        ]);
    }

    /**
     * @spec-link [[api_matchmaking]]
     */
    public function status(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        // 1. Check if in queue
        $queueEntry = \App\Models\MatchmakingQueue::where('user_id', $user->id)->first();
        if ($queueEntry) {
            $config = self::MODE_CONFIG[$queueEntry->game_mode] ?? self::MODE_CONFIG['1v1_PVP'];
            $queueCount = \App\Models\MatchmakingQueue::where('game_mode', $queueEntry->game_mode)->count();

            return $this->success([
                'status' => 'queued',
                'match_id' => null,
                'expected_participants' => $config['required'],
                'empty_slots' => max(0, $config['required'] - $queueCount),
                'queued_at' => $queueEntry->created_at->toIso8601String()
            ]);
        }

        // 2. Check if in an active match (reconnection case)
        $participant = \App\Models\MatchParticipant::where('player_id', $user->id)
            ->latest()
            ->first();

        if ($participant && $participant->match && is_null($participant->match->concluded_at)) {
             $match = $participant->match;
             $config = self::MODE_CONFIG[$match->game_mode] ?? self::MODE_CONFIG['1v1_PVP'];

             return $this->success([
                 'status' => 'matched',
                 'match_id' => $match->id,
                 'expected_participants' => $config['required'],
                 'empty_slots' => 0
             ], 'Match in progress. Reconnecting...');
        }

        // 3. User is idle
        return $this->success([
            'status' => 'idle',
            'match_id' => null,
            'expected_participants' => null,
            'empty_slots' => null
        ], 'Not in queue');
    }

    /**
     * @spec-link [[api_matchmaking]]
     */
    public function leaveMatch(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        \App\Models\MatchmakingQueue::where('user_id', $user->id)->delete();

        return $this->success(null);
    }

    /**
     * @spec-link [[ui_dashboard_match_statistics]]
     */
    public function getWaitingStats(Request $request)
    {
        $count = \App\Models\MatchmakingQueue::distinct('user_id')->count();
        return $this->success(['waiting_count' => $count], 'Waiting stats retrieved.');
    }

    /**
     * @spec-link [[ui_dashboard_match_statistics]]
     */
    public function getActiveStats(Request $request)
    {
        $stats = $this->upsilonService->getActiveMatchStats();
        
        if (!($stats['success'] ?? false)) {
             return $this->error($stats['message'] ?? 'Failed to retrieve active stats', 502);
        }

        return $this->success($stats['data'] ?? ['active_count' => 0], 'Active stats retrieved.');
    }

}