<?php

namespace Ninja\DeviceTracker\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ninja\DeviceTracker\Models\Session;

/**
 * @property Session $resource
 *
 * @mixin Session
 */
final class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => (string) $this->resource->uuid,
            'ip' => $this->resource->ip,
            'location' => $this->resource->location->array(),
            'status' => $this->resource->status->value,
            'last_activity_at' => $this->resource->last_activity_at,
            'started_at' => $this->resource->started_at,
            'finished_at' => $this->resource->finished_at,
            'device' => new DeviceResource($this->whenLoaded('device')),
        ];
    }
}
