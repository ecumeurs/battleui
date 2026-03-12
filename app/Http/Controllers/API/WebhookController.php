<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @spec-link [[api_go_webhook_callback]]
 */
class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Must validate the api_standard_envelope here or assume the engine fulfills it.
        $eventId = $request->input('request_id');
        $eventType = $request->input('data.event_type', $request->input('event_type')); // Depend on actual format
        
        Log::info("Webhook received [{$eventId}]: {$eventType}");

        // Here we'd typically map event_type to game logic or model caching.
        // For ISS-013, returning 200 OK demonstrates successful communication payload receipt.
        return response()->json([
            'status' => 'success'
        ]);
    }
}
