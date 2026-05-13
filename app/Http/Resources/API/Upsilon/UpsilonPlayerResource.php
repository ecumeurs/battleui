<?php

namespace App\Http\Resources\API\Upsilon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpsilonPlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this is an array with 'user', 'team', 'ia', 'entities' or similar
        $payload = [
            'id' => $this->resource['id'],
            'nickname' => $this->resource['nickname'] ?? 'Unknown',
            'team' => $this->resource['team'],
            'ia' => $this->resource['ia'],
            'entities' => UpsilonEntityResource::collection($this->resource['entities']),
        ];

        if ($this->resource['ia']) {
            $payload['total_wins'] = $this->resource['total_wins'];
            if (isset($this->resource['archetype'])) {
                $payload['archetype'] = $this->resource['archetype'];
            }
        }

        return $payload;
    }
}
