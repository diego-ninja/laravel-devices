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
            "browser" => $this->resource->browser,
            "browser_version" => $this->resource->browser_version,
            "platform" => $this->resource->platform,
            "platform_version" => $this->resource->platform_version,
            "device" => $this->resource->device,
            "device_type" => $this->resource->device_type,
            "is_current" => $this->resource->isCurrent(),
            "source" => $this->resource->source,
            "ip_address" => $this->resource->ip,
            "sessions" => SessionResource::collection($this->whenLoaded('sessions')),
        ];
    }
}
