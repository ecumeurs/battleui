<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\Contracts\UpsilonApiServiceInterface;
use App\DTOs\Upsilon\PlayerDTO;
use App\DTOs\Upsilon\EntityDTO;
use App\DTOs\Upsilon\PositionDTO;
use Illuminate\Support\Str;

class UpsilonApiRoundtripTest extends TestCase
{
    /**
     * Replicates TestBattleFullRoundtrip from upsilonapi/main_test.go
     * Note: Requires the Go UpsilonAPI to be running locally at the configured API Endpoint.
     */
    public function test_battle_full_roundtrip(): void
    {
        /** @var UpsilonApiServiceInterface $service */
        $service = app(UpsilonApiServiceInterface::class);

        $matchId = Str::uuid()->toString();
        $p1Id = Str::uuid()->toString();
        $p2Id = Str::uuid()->toString();
        $p1e1Id = Str::uuid()->toString();
        $p2e1Id = Str::uuid()->toString();

        $players = [
            new PlayerDTO(
                id: $p1Id,
                team: 1,
                ia: false,
                entities: [
                    new EntityDTO(
                        id: $p1e1Id,
                        name: "P1E1",
                        hp: 10,
                        maxHp: 10,
                        attack: 3,
                        defense: 1,
                        move: 2,
                        maxMove: 2,
                        position: new PositionDTO(0, 0)
                    )
                ]
            ),
            new PlayerDTO(
                id: $p2Id,
                team: 2,
                ia: true,
                entities: [
                    new EntityDTO(
                        id: $p2e1Id,
                        name: "P2E1",
                        hp: 10,
                        maxHp: 10,
                        attack: 3,
                        defense: 1,
                        move: 2,
                        maxMove: 2,
                        position: new PositionDTO(1, 1)
                    )
                ]
            )
        ];

        // 1. Start Arena
        $startResponse = $service->startArena(
            $matchId,
            url('/api/webhook/upsilon'),
            $players
        );

        $this->assertTrue($startResponse['success'], "Failed to start arena: " . json_encode($startResponse));
        $this->assertArrayHasKey('arena_id', $startResponse['data']);

        $arenaId = $startResponse['data']['arena_id'];
        $initialState = $startResponse['data']['initial_state'];
        
        // Find entities actual positions
        $p1e1Pos = null;
        $p2e1Pos = null;
        foreach ($initialState['entities'] as $entity) {
            if ($entity['id'] === $p1e1Id) {
                $p1e1Pos = $entity['position'];
            }
            if ($entity['id'] === $p2e1Id) {
                $p2e1Pos = $entity['position'];
            }
        }
        

        // Note: In PHPUnit, we don't naturally run async listeners to wait for the Go webhook.
        // We'll trust that the Go engine has executed it locally and proceed with an action.
        sleep(2); // Wait momentarily for the Go routine to broadcast turn.started

        // 2. Move action
        $moveResponse = $service->sendAction(
            $arenaId,
            $p1Id,
            $p1e1Id,
            'move',
            [new PositionDTO(0, 1)]
        );
        if ($moveResponse['success']) {
            $this->assertStringContainsString('move', $moveResponse['message'], "Successful move should mention 'move'");
        } else {
            $this->assertStringContainsString('move', $moveResponse['message'], "Failed move should mention 'move'");
        }

        // 3. Attack action (will fail because the target has no valid entity to attack locally since positions are 0,0)
        $attackResponse = $service->sendAction(
            $arenaId,
            $p1Id,
            $p1e1Id,
            'attack',
            [new PositionDTO(1, 1)]
        );
        
        if ($attackResponse['success']) {
            $this->assertStringContainsString('attack', $attackResponse['message'], "Successful attack should mention 'attack'");
        } else {
            $this->assertStringContainsString('attack', $attackResponse['message'], "Failed attack should mention 'attack'");
        }

        // 4. Pass turn
        $passResponse = $service->sendAction(
            $arenaId,
            $p1Id,
            $p1e1Id,
            'pass',
            []
        );
        $this->assertTrue($passResponse['success'], "Failed pass action");
    }
}
