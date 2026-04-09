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
}
