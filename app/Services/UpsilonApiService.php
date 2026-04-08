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
        $this->baseUrl = config('services.upsilon.url', 'http://localhost:8081/internal');
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

        return $this->sendEnvelopeRequest('POST', '/arena/start', $payload, 'Start Arena');
    }

    /**
     * @spec-link [[api_go_battle_action]]
     */
    public function sendAction(string $arenaId, string $playerId, string $entityId, string $type, array $targetCoords = []): array
    {
        $payload = [
            'player_id' => $playerId,
            'entity_id' => $entityId,
            'type' => $type,
        ];

        if (!empty($targetCoords)) {
            $payload['target_coords'] = array_map(fn($pos) => is_array($pos) ? $pos : $pos->toArray(), $targetCoords);
        }

        return $this->sendEnvelopeRequest('POST', "/arena/{$arenaId}/action", $payload, "Action: {$type}");
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

            if (!$response->successful()) {
                Log::error("Upsilon API Error [{$requestId}]: " . $response->body());
            }

            return $response->json() ?? [];

        } catch (\Exception $e) {
            Log::error("Upsilon API Connection Failed [{$requestId}]: " . $e->getMessage());
            
            return [
                'request_id' => $requestId,
                'message' => 'Connection to Game Engine failed',
                'success' => false,
                'data' => [],
                'meta' => ['exception' => $e->getMessage()]
            ];
        }
    }
}
