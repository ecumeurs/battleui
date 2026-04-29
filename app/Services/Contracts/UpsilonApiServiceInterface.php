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

    public function sendAction(string $arenaId, string $playerId, string $entityId, string $type, array $targetCoords = [], ?string $skillId = null): array;
    
    public function forfeit(string $arenaId, string $playerId): array;

    /**
     * Get active match statistics from the Upsilon Engine.
     * 
     * @return array The Go api_standard_envelope response (containing ActiveMatchStatsResponse data)
     */
    public function getActiveMatchStats(): array;

    /**
     * Check if a specific arena exists in the Upsilon Engine.
     *
     * @param string $arenaId
     * @return array The Go api_standard_envelope response (containing ArenaExistsResponse data)
     */
    public function checkArenaExistence(string $arenaId): array;

    /**
     * Resurrect a crashed arena from persisted board state (ISS-054).
     * Called when the engine has lost in-memory state for an active match.
     *
     * @param string $matchId The match to resurrect
     * @param array $boardState The cached game_state_cache snapshot from the DB
     * @param string $callbackUrl Webhook endpoint for engine events
     * @param array $players Original player list (needed to re-create controllers)
     * @return array The Go api_standard_envelope response (containing ArenaStartResponse data)
     */
    public function resurrectArena(string $matchId, array $boardState, string $callbackUrl, array $players): array;
}
