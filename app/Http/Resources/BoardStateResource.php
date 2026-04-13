<?php
/*
 * @spec-link [[arch_api_id_masking_gateway]]
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardStateResource extends JsonResource
{
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

        // Mask current_player_id -> We should provide the team instead if possible, 
        // but for now let's just strip it if we can't map it.
        // Actually, the engine seems to provide team IDs in the players array.
        unset($data['current_player_id']);
        unset($data['winner_id']);

        // Mask entities
        if (isset($data['entities']) && is_array($data['entities'])) {
            foreach ($data['entities'] as &$entity) {
                unset($entity['player_id']);
                // id remains for characters/entities (Character ID)
            }
        }

        // Mask turn sequence
        if (isset($data['turn']) && is_array($data['turn'])) {
            foreach ($data['turn'] as &$turn) {
                unset($turn['player_id']);
            }
        }

        // Mask players array
        if (isset($data['players']) && is_array($data['players'])) {
            foreach ($data['players'] as &$player) {
                unset($player['id']); // User ID

                // Entities nested in players
                if (isset($player['entities']) && is_array($player['entities'])) {
                    foreach ($player['entities'] as &$entity) {
                        unset($entity['player_id']);
                    }
                }
            }
        }

        return $data;
    }
}
