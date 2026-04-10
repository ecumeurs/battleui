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
        return [
            'id' => $this->resource['id'],
            'nickname' => $this->resource['nickname'] ?? 'Unknown',
            'team' => $this->resource['team'],
            'ia' => $this->resource['ia'],
            'entities' => UpsilonEntityResource::collection($this->resource['entities']),
        ];
    }
}
