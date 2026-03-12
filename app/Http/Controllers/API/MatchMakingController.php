<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Contracts\UpsilonApiServiceInterface;
use App\DTOs\Upsilon\PlayerDTO;
use App\DTOs\Upsilon\EntityDTO;
use Illuminate\Support\Str;

/**
 * @spec-link [[api_go_battle_start]]
 */
class MatchMakingController extends Controller
{
    public function __construct(
        protected UpsilonApiServiceInterface $upsilonService
    ) {}

    public function joinMatch(Request $request, string $id)
    {
        // Simplified Logic: When 2 players joined, we trigger StartArena.
        
        // Mock payload definition based on the go test
        $p1 = new PlayerDTO(
            id: Str::uuid()->toString(),
            team: 1,
            ia: false,
            entities: [
                new EntityDTO(
                    id: Str::uuid()->toString(),
                    name: "Player 1 Entity",
                    hp: 10,
                    maxHp: 10,
                    attack: 3,
                    defense: 1,
                    move: 2,
                    maxMove: 2
                )
            ]
        );

        $response = $this->upsilonService->startArena(
            $id,
            url('/api/webhook/upsilon'), // Provide the webhook callback
            [$p1]
        );

        return response()->json($response);
    }

    public function leaveMatch(Request $request, string $id)
    {
        
    }

}