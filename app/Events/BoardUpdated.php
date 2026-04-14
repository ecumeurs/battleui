<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\BoardStateResource;

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
        public string $user_channel_key,
        public string $match_id,
        public array $data,
        public $recipient = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user_channel_key),
        ];
    }

    public function broadcastAs(): string
    {
        return 'board.updated';
    }

    public function broadcastWith(): array
    {
        // 1. Fetch current team mapping for the match
        $match = \App\Models\GameMatch::find($this->match_id);
        $teams = [];
        if ($match) {
            foreach ($match->participants as $p) {
                $teams[$p->player_id] = $p->team;
            }
        }

        // 2. Wrap payload with team mapping so BoardStateResource can process it
        $payload = $this->data;
        $payload['players_teams'] = $teams;

        // 3. Use the standardized resource for consistent masking
        // Use the injected recipient for identity-aware masking
        $resource = new BoardStateResource($payload, $this->recipient);
        
        // 4. Wrap with Standard JSON Envelope [[api_standard_envelope]]
        return [
            'request_id' => (string) \Illuminate\Support\Str::uuid7(),
            'message' => 'Board Updated',
            'success' => true,
            'data' => array_merge(
                ['match_id' => $this->match_id],
                $resource->resolve()
            ),
            'meta' => (object) []
        ];
    }
}
