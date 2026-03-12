<?php

namespace App\Services\Contracts;

use App\DTOs\Upsilon\PlayerDTO;

interface UpsilonApiServiceInterface
{
    /**
     * Start a new Arena match in the Upsilon Engine.
     *
     * @param string $matchId The unique match ID
     * @param string $callbackUrl Webhook endpoint for the engine to push events to
     * @param PlayerDTO[] $players Array of PlayerDTO objects representing the participants
     * @return array The Go api_standard_envelope response (containing ArenaStartResponse data)
     */
    public function startArena(string $matchId, string $callbackUrl, array $players): array;

    /**
     * Send an action to an active Arena in the Upsilon Engine.
     *
     * @param string $arenaId UUID of the active arena
     * @param string $playerId Controller ID of the player
     * @param string $entityId ID of the entity performing the action
     * @param string $type The action type (Move, Attack, Pass, etc)
     * @param array $targetCoords Array of PositionDTO objects (for targeting hexes)
     * @return array The Go api_standard_envelope response
     */
    public function sendAction(string $arenaId, string $playerId, string $entityId, string $type, array $targetCoords = []): array;
}
