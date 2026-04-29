<?php

namespace App\Services;

use App\Services\Contracts\UpsilonApiServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @spec-link [[api_go_battle_engine]]
 */
class UpsilonApiService implements UpsilonApiServiceInterface
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.upsilon.url');
    }

    /**
     * @spec-link [[api_go_battle_start]]
     */
    public function startArena(string $matchId, string $callbackUrl, array $players): array
    {
        $payload = [
            'match_id' => $matchId,
            'callback_url' => $callbackUrl,
            'players' => array_map(fn($player) => $player instanceof \Illuminate\Http\Resources\Json\JsonResource ? $player->resolve() : $player, $players),
        ];

        // Let's dump the finalized payload arrays
        // dump("Start Arena payload", $payload);

        return $this->sendEnvelopeRequest('POST', '/v1/arena/start', $payload, 'Start Arena');
    }

    /**
     * @spec-link [[api_go_battle_action]]
     */
    public function sendAction(string $arenaId, string $playerId, string $entityId, string $type, array $targetCoords = [], ?string $skillId = null): array
    {
        $payload = [
            'player_id' => $playerId,
            'entity_id' => $entityId,
            'type' => $type,
        ];

        if (!empty($targetCoords)) {
            $payload['target_coords'] = array_map(fn($pos) => is_array($pos) ? $pos : $pos->toArray(), $targetCoords);
        }

        if ($skillId) {
            $payload['skill_id'] = $skillId;
        }

        return $this->sendEnvelopeRequest('POST', "/v1/arena/{$arenaId}/action", $payload, "Action: {$type}");
    }

    /**
     * @spec-link [[ui_dashboard_match_statistics]]
     */
    public function getActiveMatchStats(): array
    {
        return $this->sendEnvelopeRequest('GET', '/v1/match/stats/active', [], 'Get Active Match Stats');
    }

    /**
     * @spec-link [[api_arena_existence_check]]
     */
    public function checkArenaExistence(string $arenaId): array
    {
        return $this->sendEnvelopeRequest('GET', "/v1/arena/{$arenaId}/exists", [], 'Check Arena Existence');
    }

    /**
     * @spec-link [[api_go_battle_forfeit]]
     */
    public function forfeit(string $arenaId, string $playerId): array
    {
        $payload = [
            'player_id' => $playerId,
        ];

        return $this->sendEnvelopeRequest('POST', "/v1/arena/{$arenaId}/forfeit", $payload, "Action: forfeit");
    }

    /**
     * Resurrect a crashed arena from persisted board state (ISS-054).
     * Translates the cached BoardState snapshot into the Go engine's ArenaResurrectRequest shape.
     * @spec-link [[api_go_battle_engine]]
     */
    public function resurrectArena(string $matchId, array $boardState, string $callbackUrl, array $players): array
    {
        // Translate the 2D Cells[][] snapshot to ResurrectGrid.cells format.
        $gridData = $boardState['grid'] ?? [];
        $cells = [];
        $rawCells = $gridData['cells'] ?? [];
        foreach ($rawCells as $col) {
            $colOut = [];
            foreach ($col as $c) {
                $colOut[] = [
                    'obstacle' => (bool)($c['obstacle'] ?? false),
                    'height'   => (int)($c['height'] ?? 0),
                ];
            }
            $cells[] = $colOut;
        }

        // Translate Turn[] to ResurrectTurn[].
        $turns = array_map(fn($t) => [
            'entity_id' => $t['entity_id'] ?? '',
            'delay'     => (int)($t['delay'] ?? 0),
        ], $boardState['turn'] ?? []);

        $payload = [
            'match_id'          => $matchId,
            'callback_url'      => $callbackUrl,
            'players'           => $players,
            'grid'              => [
                'width'      => (int)($gridData['width'] ?? 0),
                'height'     => (int)($gridData['height'] ?? 0),
                'max_height' => (int)($gridData['max_height'] ?? 0),
                'cells'      => $cells,
            ],
            'turns'             => $turns,
            'current_entity_id' => $boardState['current_entity_id'] ?? '',
            'version'           => (int)($boardState['version'] ?? 0),
        ];

        return $this->sendEnvelopeRequest('POST', "/v1/arena/{$matchId}/resurrect", $payload, 'Resurrect Arena');
    }

    /**
     * Wraps requests in the api_standard_envelope format.
     * @spec-link [[api_standard_envelope]]
     */
    protected function sendEnvelopeRequest(string $method, string $endpoint, array $data, string $message): array
    {
        $frontendRequestId = request()->header('X-Request-ID');
        $requestId = $frontendRequestId ?: Str::uuid()->toString();

        $envelope = [
            'request_id' => $requestId,
            'message' => $message,
            'success' => true,
            'data' => $data,
            'meta' => new \stdClass()
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->send($method, $this->baseUrl . $endpoint, [
                        'json' => $envelope
                    ]);

            $json = $response->json();

            // An engine rule rejection (e.g. 412 PreconditionFailed) still carries
            // a valid envelope with message + meta.error_key. Preserve it instead
            // of throwing — the controller decides how to surface the failure.
            // @spec-link [[api_standard_envelope]]
            if (!$response->successful()) {
                if (is_array($json) && array_key_exists('success', $json)) {
                    Log::warning("Upsilon API rule error [{$requestId}]: " . ($json['message'] ?? 'n/a'));
                    return $json;
                }
                Log::error("Upsilon API Error [{$requestId}]: " . $response->body());
                throw new \App\Exceptions\EngineConnectionException("API returned status {$response->status()}");
            }

            if (is_null($json)) {
                throw new \App\Exceptions\EngineConnectionException("API returned invalid JSON");
            }

            return $json;

        } catch (\Exception $e) {
            if ($e instanceof \App\Exceptions\EngineConnectionException) {
                throw $e;
            }
            
            Log::error("Upsilon API Connection Failed [{$requestId}]: " . $e->getMessage());
            throw new \App\Exceptions\EngineConnectionException($e->getMessage());
        }
    }
}
