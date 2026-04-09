<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFound implements ShouldBroadcast
{
    /**
     * @spec-link [[api_websocket_game_events]]
     */
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $user_id,
        public string $match_id,
        public array $data = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.found';
    }
}
