<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\UpsilonApiServiceInterface;

/**
 * @spec-link [[api_go_battle_action]]
 */
class GameController extends Controller
{
    public function __construct(
        protected UpsilonApiServiceInterface $upsilonService
    ) {}

    public function state(Request $request, string $id)
    {
        // Placeholder for GET /arena/{id} logic once it exists in engine
    }

    /**
     * @spec-link [[api_battle_proxy]]
     * @spec-link [[api_go_battle_action]]
     */
    public function action(Request $request, string $id)
    {
        $frontendRequestId = $request->header('X-Request-ID', (string) str()->uuid());

        // 1. Validate payload from frontend
        $validated = $request->validate([
            'player_id' => 'required|string',
            'entity_id' => 'required|string',
            'type' => 'required|string',
            'target_coords' => 'array',
        ]);

        // 2. Call the UPSILON ENGINE via Service
        $response = $this->upsilonService->sendAction(
            $id,
            $validated['player_id'],
            $validated['entity_id'],
            $validated['type'],
            $validated['target_coords'] ?? []
        );

        // 3. Return Standard Envelope back
        return response()->json($response, ($response['success'] ?? false) ? 200 : 400);
    }
}