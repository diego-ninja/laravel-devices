<?php

namespace Ninja\DeviceTracker\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Ninja\DeviceTracker\Models\Device;

/**
 * @property Device $resource
 */
final class DeviceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "uuid" => $this->resource->uuid,
            "status" => $this->resource->status,
            "browser" => [
                "name" => $this->resource->browser,
                "version" => $this->resource->browser_version,
                "family" => $this->resource->browser_family,
            ],
            "platform" => [
                "name" => $this->resource->platform,
                "version" => $this->resource->platform_version,
                "family" => $this->resource->platform_family,
            ],
            "device" => [
                "family" => $this->resource->device,
                "model" => $this->resource->device_model,
                "type" => $this->resource->device_type,
            ],
            "is_current" => $this->resource->isCurrent(),
            "source" => $this->resource->source,
            "ip_address" => $this->resource->ip,
            "sessions" => SessionResource::collection($this->whenLoaded('sessions')),
        ];
    }
}
