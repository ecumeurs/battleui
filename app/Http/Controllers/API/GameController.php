<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\UpsilonApiServiceInterface;
use App\Traits\ApiResponder;
use App\Http\Requests\API\Game\ActionRequest;
use App\Http\Resources\BoardStateResource;

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
        
        // Ensure only participants or Admins can view the state
        $this->authorize('view', $match);

        $participants = \App\Models\MatchParticipant::where('match_id', $id)->get();
        $playerTeams = $participants->pluck('team', 'player_id')->toArray();
        
        $gameState = $match->game_state_cache;
        $gameState['players_teams'] = $playerTeams;

        return $this->success([
            'match_id' => $match->id,
            'game_mode' => $match->game_mode,
            'game_state' => (new BoardStateResource($gameState))->resolve(),
            'started_at' => $match->started_at?->toIso8601String(),
            'concluded_at' => $match->concluded_at?->toIso8601String(),
            'winner_team_id' => $match->winning_team_id,
        ]);
    }

    /**
     * @spec-link [[api_battle_proxy]]
     * @spec-link [[api_go_battle_action]]
     */
    public function action(ActionRequest $request, string $id)
    {
        $match = \App\Models\GameMatch::findOrFail($id);

        // 1. Authorize: Only participants can send actions
        $this->authorize('action', $match);

        $frontendRequestId = $request->header('X-Request-ID', (string) str()->uuid());

        // 2. Validate payload from frontend
        $validated = $request->validated();

        // 3. Ownership Verification: Ensure the user owns the entity they are acting with
        $user = $request->user();
        $entityId = $validated['entity_id'];
        
        $ownsEntity = \App\Models\Character::where('id', $entityId)
            ->where('player_id', $user->id)
            ->exists();

        if (!$ownsEntity) {
            return $this->error('Forbidden: You do not own the entity specified in this action.', 403);
        }

        // 4. Call the UPSILON ENGINE via Service
        $response = $this->upsilonService->sendAction(
            $id,
            $user->id,
            $entityId,
            $validated['type'],
            $validated['target_coords'] ?? []
        );

        // 3. Return Standard Envelope back
        return $this->success($response['data'] ?? $response, $response['message'] ?? 'Action processed', ($response['success'] ?? false) ? 200 : 400);
    }

    /**
     * @spec-link [[rule_forfeit_battle]]
     */
    public function forfeit(Request $request, string $id)
    {
        $match = \App\Models\GameMatch::findOrFail($id);
        
        // Only participants can forfeit
        $this->authorize('forfeit', $match);

        // Forfeiting is a player-level action, no entity_id required from frontend.
        $response = $this->upsilonService->forfeit($id, $request->user()->id);

        return $this->success(
            $response['data'] ?? [], 
            $response['message'] ?? 'Forfeit request processed', 
            ($response['success'] ?? false) ? 200 : 400
        );
    }
}