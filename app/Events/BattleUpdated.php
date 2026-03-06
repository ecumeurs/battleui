<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $battleData, 
        public int $battleId
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('battle.' . $this->battleId);
    }

    public function broadcastAs(): string
    {
        // This is the name your Vue app or Postman listens for
        return 'battle.event';
    }
}
