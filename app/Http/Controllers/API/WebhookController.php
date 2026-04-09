<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @spec-link [[api_go_webhook_callback]]
 * @spec-link [[api_websocket_game_events]]
 */
use App\Models\GameMatch;
use App\Events\BoardUpdated;
use App\Traits\ApiResponder;

class WebhookController extends Controller
{
    use ApiResponder;

    /**
     * @spec-link [[api_battle_proxy]]
     * @spec-link [[api_go_webhook_callback]]
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $match_id = $payload['data']['match_id'] ?? $payload['match_id'] ?? null;

        if (!$match_id) {
            Log::error("Webhook received with no match_id", $payload);
            return $this->error('Webhook received with no match_id', 400);
        }

        $match = GameMatch::find($match_id);
        if ($match) {
            $match->update([
                'game_state_cache' => $payload['data'] ?? $payload,
                'turn' => $payload['data']['turn_counter'] ?? $match->turn,
            ]);

            Log::info("Match {$match_id} state updated from webhook.");

            // Broadcast the update
            broadcast(new BoardUpdated($match_id, $payload['data'] ?? $payload));
        }

        return $this->success(null, 'Webhook processed successfully.');
    }
}
