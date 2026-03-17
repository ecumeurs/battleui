<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @spec-link [[api_go_webhook_callback]]
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
        $matchId = $payload['data']['match_id'] ?? $payload['match_id'] ?? null;

        if (!$matchId) {
            Log::error("Webhook received with no match_id", $payload);
            return $this->error('Webhook received with no match_id', 400);
        }

        $match = GameMatch::find($matchId);
        if ($match) {
            $match->update([
                'game_state_cache' => $payload['data'] ?? $payload,
                'turn' => $payload['data']['turn_counter'] ?? $match->turn,
            ]);

            Log::info("Match {$matchId} state updated from webhook.");

            // Broadcast the update
            broadcast(new BoardUpdated($matchId, $payload['data'] ?? $payload));
        }

        return $this->success(null, 'Webhook processed successfully.');
    }
}
