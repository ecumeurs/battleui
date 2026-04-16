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
use App\Http\Requests\API\Webhook\WebhookRequest;

/**
 * @spec-link [[api_go_webhook_callback]]
 * @spec-link [[api_websocket_game_events]]
 * @spec-link [[api_standard_envelope]]
 */
class WebhookController extends Controller
{
    use ApiResponder;

    /**
     * @spec-link [[api_battle_proxy]]
     * @spec-link [[api_go_webhook_callback]]
     * @spec-link [[mech_game_state_versioning]]
     * @spec-link [[battleui_api_dtos]]
     */
    public function handle(WebhookRequest $request)
    {
        // Strictly use validated data from WebhookRequest
        $arenaEvent = $request->validated()['data'];
        $match_id = $arenaEvent['match_id'];
        $incomingVersion = $arenaEvent['version'];
        $boardState = $arenaEvent['data']; // Extract core board state

        // Eager load participants and their players so masking logic in BoardUpdated has data ready
        $match = GameMatch::with(['participants.player'])->find($match_id);
        
        if ($match) {
            $eventName = $arenaEvent['event_type'] ?? 'board.updated';
            
            // Logic Detail: [[mech_game_state_versioning]]
            // Only accept updates with a higher version than currently stored,
            // OR the same version if the event type is different (e.g., Board Updated followed by Turn Started).
            $storedVersion = $match->version;
            $lastEventType = $match->game_state_cache['_atd_meta']['last_event_type'] ?? null;

            if ($match->version > 0) {
                if ($incomingVersion < $storedVersion) {
                    Log::debug("Ignoring stale state for match {$match_id}. Incoming: v{$incomingVersion}, Stored: v{$storedVersion}");
                    return $this->success(['match_id' => $match_id], 'State ignored (stale version).');
                }
                
                if ($incomingVersion == $storedVersion && $eventName === $lastEventType) {
                    Log::debug("Ignoring duplicate event for match {$match_id}. Incoming: v{$incomingVersion} ($eventName)");
                    return $this->success(['match_id' => $match_id], 'State ignored (duplicate event).');
                }
            }

            // Inject metadata to track the last event type for this version
            $boardState['_atd_meta'] = [
                'last_event_type' => $eventName,
                'processed_at' => now()->toIso8601String(),
            ];

            // Unify Turn State: [[mech_game_state_versioning]]
            // version/sequence is the single source of truth for progression.
            $match->update([
                'game_state_cache' => $boardState,
                'version' => $incomingVersion,
                'turn' => $incomingVersion, // Unified progression marker
            ]);

            Log::info("Match {$match_id} state updated to version {$incomingVersion} ($eventName). Broadcasting to " . $match->participants->count() . " participants.");

            // Broadcast the validated update
            foreach ($match->participants as $participant) {
                $user = $participant->player;
                if ($user && $user->ws_channel_key) {
                    broadcast(new BoardUpdated(
                        $user->ws_channel_key, 
                        $match_id, 
                        $boardState,
                        $user,
                        $eventName
                    ));
                }
            }
        }

        return $this->success(['match_id' => $match_id, 'version' => $incomingVersion], 'Webhook processed successfully.');
    }
}
