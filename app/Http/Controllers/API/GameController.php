<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\UpsilonApiServiceInterface;
use App\Traits\ApiResponder;
use App\Http\Requests\API\Game\ActionRequest;

/**
 * @spec-link [[api_go_battle_action]]
 */
class GameController extends Controller
{
    use ApiResponder;

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
    public function action(ActionRequest $request, string $id)
    {
        $frontendRequestId = $request->header('X-Request-ID', (string) str()->uuid());

        // 1. Validate payload from frontend
        $validated = $request->validated();

        // 2. Call the UPSILON ENGINE via Service
        $response = $this->upsilonService->sendAction(
            $id,
            $validated['player_id'],
            $validated['entity_id'],
            $validated['type'],
            $validated['target_coords'] ?? []
        );

        // 3. Return Standard Envelope back
        return $this->success($response['data'] ?? $response, $response['message'] ?? 'Action processed', ($response['success'] ?? false) ? 200 : 400);
    }
}