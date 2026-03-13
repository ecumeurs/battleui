<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoardUpdated implements ShouldBroadcast
{
    /**
     * @spec-link [[api_battle_proxy]]
     */
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $matchId,
        public array $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('arena.' . $this->matchId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'board.updated';
    }
}
