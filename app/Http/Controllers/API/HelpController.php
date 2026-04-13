<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CodeDiscoveryService;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;

/**
 * @spec-link [[api_help_endpoint]]
 */
class HelpController extends Controller
{
    use ApiResponder;

    public function __construct(
        protected CodeDiscoveryService $discoveryService
    ) {}

    /**
     * Display a listing of all documented API endpoints and WebSocket events.
     */
    public function index(): JsonResponse
    {
        $endpoints = $this->discoveryService->getEndpoints();
        $websockets = $this->discoveryService->getWebsockets();
        $dtoRegistry = $this->discoveryService->getDtoRegistry();

        return $this->success([
            'version' => '1.2.0',
            'api_base_url' => url('/api/v1'),
            'envelope' => [
                'request_id' => 'string (UUIDv7): Unique trace identifier for the request lifecycle.',
                'message' => 'string: Human-readable summary of the operation result.',
                'success' => 'boolean: Technical success indicator.',
                'data' => 'object|array|null: The primary response payload.',
                'meta' => 'object: Optional debugging or supplemental information.'
            ],
            'endpoints' => $endpoints,
            'websocket' => $websockets,
            'dto_registry' => $dtoRegistry,
            'workflow' => [
                'onboarding' => '1. Register -> 2. Login -> 3. Reroll/Rename characters.',
                'battle' => '1. Join Matchmaking -> 2. Receive match.found WS event -> 3. Subscribe to arena WS -> 4. Execute game_action.'
            ]
        ], 'API Documentation accurately introspected from system source code.');
    }
}
