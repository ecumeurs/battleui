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
        $match = \App\Models\GameMatch::findOrFail($id);
        $participants = \App\Models\MatchParticipant::where('match_id', $id)
            ->with('player:id,nickname')
            ->get();

        return $this->success([
            'match_id' => $match->id,
            'game_mode' => $match->game_mode,
            'game_state' => $match->game_state_cache,
            'participants' => $participants->map(fn($p) => [
                'player_id' => $p->player_id,
                'nickname' => $p->player?->nickname ?? 'Unknown',
                'team' => $p->team,
            ]),
            'started_at' => $match->started_at?->toIso8601String(),
            'concluded_at' => $match->concluded_at?->toIso8601String(),
            'winning_team_id' => $match->winning_team_id,
        ]);
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