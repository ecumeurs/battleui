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

class WebhookController extends Controller
{
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
            return response()->json(['success' => false], 400);
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

        return response()->json([
            'request_id' => $request->header('X-Request-ID', $payload['request_id'] ?? (string) str()->uuid()),
            'message' => 'Webhook processed successfully.',
            'success' => true,
            'data' => null,
        ]);
    }
}
