<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP bridge to the Go engine's skill generation endpoint.
 *
 * @spec-link [[api_skill_generate_engine]]
 */
class SkillGeneratorBridge
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.upsilon.url');
    }

    /**
     * Call POST /v1/skills/generate and return the skill data array.
     *
     * @return array{id: string, name: string, behavior: string, targeting: array, costs: array, effect: array, grade: string, weight_positive: int, weight_negative: int}
     * @throws SkillServiceException with ERR_GENERATOR_UNREACHABLE on failure.
     */
    public function generate(): array
    {
        try {
            $response = Http::timeout(5)->post("{$this->baseUrl}/v1/skills/generate", []);

            if (! $response->successful()) {
                Log::error('[SkillGeneratorBridge] Engine returned non-2xx: ' . $response->status());
                throw new SkillServiceException(
                    'Skill generator unavailable.',
                    503,
                    SkillServiceException::ERR_GENERATOR_UNREACHABLE,
                );
            }

            $json = $response->json();

            if (! isset($json['data'])) {
                throw new SkillServiceException(
                    'Invalid skill generator response.',
                    503,
                    SkillServiceException::ERR_GENERATOR_UNREACHABLE,
                );
            }

            return $json['data'];
        } catch (SkillServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[SkillGeneratorBridge] Connection failed: ' . $e->getMessage());
            throw new SkillServiceException(
                'Skill generator unreachable.',
                503,
                SkillServiceException::ERR_GENERATOR_UNREACHABLE,
            );
        }
    }
}
