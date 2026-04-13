<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @spec-link [[api_websocket_game_events]]
 * @spec-link [[api_battle_proxy]]
 */
class BoardUpdated implements ShouldBroadcast
{
    /**
     * @spec-link [[api_battle_proxy]]
     */
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $match_id,
        public array $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('arena.' . $this->match_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'board.updated';
    }

    public function broadcastWith(): array
    {
        $payload = $this->data;

        // Custom masking logic for WebSocket (similar to BoardStateResource)
        unset($payload['current_player_id']);
        unset($payload['winner_id']);

        if (isset($payload['entities']) && is_array($payload['entities'])) {
            foreach ($payload['entities'] as &$entity) {
                unset($entity['player_id']);
            }
        }

        if (isset($payload['players']) && is_array($payload['players'])) {
            foreach ($payload['players'] as &$player) {
                unset($player['id']);
                if (isset($player['entities']) && is_array($player['entities'])) {
                    foreach ($player['entities'] as &$entity) {
                        unset($entity['player_id']);
                    }
                }
            }
        }

        return [
            'match_id' => $this->match_id,
            'data' => $payload
        ];
    }
}
