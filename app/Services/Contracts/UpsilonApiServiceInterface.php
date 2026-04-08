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

    public function sendAction(string $arenaId, string $playerId, string $entityId, string $type, array $targetCoords = []): array;

    /**
     * Get active match statistics from the Upsilon Engine.
     * 
     * @return array The Go api_standard_envelope response (containing ActiveMatchStatsResponse data)
     */
    public function getActiveMatchStats(): array;
}
