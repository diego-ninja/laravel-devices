<?php

namespace Ninja\DeviceTracker\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Detection\DTO\Browser;
use Ninja\DeviceTracker\Modules\Detection\DTO\Platform;

/**
 * @property Device $resource
 *
 * @mixin Device
 */
final class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => (string) $this->resource->uuid,
            'fingerprint' => $this->whenNotNull($this->resource->fingerprint),
            'status' => $this->resource->status,
            'verified_at' => $this->when($this->resource->status === DeviceStatus::Verified, $this->resource->verified_at),
            'browser' => $this->browser($this->resource),
            'platform' => $this->platform($this->resource),
            'device' => $this->device($this->resource),
            'is_current' => $this->resource->isCurrent(),
            'source' => $this->resource->source,
            'ip_address' => $this->resource->ip,
            'grade' => $this->when($this->resource->grade !== null, $this->resource->grade),
            'metadata' => $this->resource->metadata,
            'sessions' => SessionResource::collection($this->whenLoaded('sessions')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function browser(Device $device): array
    {
        return Browser::from([
            'name' => $device->browser,
            'version' => $device->browser_version,
            'family' => $device->browser_family,
            'engine' => $device->browser_engine,
        ])->array();
    }

    /**
     * @return array<string, mixed>
     */
    private function platform(Device $device): array
    {
        return Platform::from([
            'name' => $device->platform,
            'version' => $device->platform_version,
            'family' => $device->platform_family,
        ])->array();
    }

    /**
     * @return array<string, mixed>
     */
    private function device(Device $device): array
    {
        return [
            'family' => $device->device_family,
            'model' => $device->device_model,
            'type' => $device->device_type,
        ];
    }
}
