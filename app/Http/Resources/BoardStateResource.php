<?php
/*
 * @spec-link [[arch_api_id_masking_gateway]]
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardStateResource extends JsonResource
{
    protected $forcedUser = null;

    public function __construct($resource, $user = null)
    {
        parent::__construct($resource);
        $this->forcedUser = $user;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        if (!is_array($data)) {
            return [];
        }

        $user = $this->forcedUser ?? $request->user();
        $userId = $user ? $user->id : null;

        // 1. Mask Global Turn Logic
        $data['current_player_is_self'] = ($data['current_player_id'] ?? null) === $userId;
        unset($data['current_player_id']);
        
        $data['game_finished'] = (isset($data['winner_id']) && $data['winner_id'] !== "") || (isset($data['winner_team_id']) && $data['winner_team_id'] != 0);
        if ($data['game_finished']) {
            $data['winner_team_id'] ??= 0;
            unset($data['winner_id']);
        }

        // 2. Process Players (The new Source of Truth)
        if (isset($data['players']) && is_array($data['players'])) {
            foreach ($data['players'] as &$player) {
                $player['is_self'] = ($player['id'] ?? null) === $userId;
                unset($player['id']); // User ID Masking

                if (isset($player['entities']) && is_array($player['entities'])) {
                    foreach ($player['entities'] as &$entity) {
                        $entity['is_self'] = $player['is_self'];
                        $entity['dead'] = ($entity['hp'] ?? 1) <= 0;
                        unset($entity['player_id']);
                        // Note: entity['team'] is now provided by the engine in Entity DTO
                    }
                }
            }
        }

        // 3. Process Turn Sequence
        if (isset($data['turn']) && is_array($data['turn'])) {
            foreach ($data['turn'] as &$turn) {
                $turn['is_self'] = ($turn['player_id'] ?? null) === $userId;
                // Team is typically known by looking up the player_id in the engine, 
                // but we can also inject it here if needed. 
                // For now, mapping via player_teams passed from controller.
                $turn['team'] = $data['players_teams'][$turn['player_id'] ?? ''] ?? 0;
                unset($turn['player_id']);
            }
        }

        // 4. Remove Redundant Flat Entities Array (Consolidation)
        unset($data['entities']);
        unset($data['players_teams']);

        return $data;
    }
}
