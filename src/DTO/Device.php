<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Device implements JsonSerializable, Stringable
{
    public function __construct(
        public string $uuid,
        public string $status,
        public string $browser,
        public string $browserVersion,
        public string $platform,
        public string $platformVersion,
        public string $device,
        public string $deviceType,
        public bool $isCurrent,
        public ?string $userAgent
    ) {
    }
    public static function fromModel(\Ninja\DeviceTracker\Models\Device $device): self
    {
        return new self(
            uuid: $device->uuid->toString(),
            status: $device->status->value,
            browser: $device->browser,
            browserVersion: $device->browser_version,
            platform: $device->platform,
            platformVersion: $device->platform_version,
            device: $device->device,
            deviceType: $device->device_type,
            isCurrent: $device->isCurrent(),
            userAgent: $device->source
        );
    }

    public function array(): array
    {
        return [
            "uuid" => $this->uuid,
            "status" => $this->status,
            "browser" => $this->browser,
            "browserVersion" => $this->browserVersion,
            "platform" => $this->platform,
            "platformVersion" => $this->platformVersion,
            "device" => $this->device,
            "deviceType" => $this->deviceType,
            "isCurrent" => $this->isCurrent,
            "userAgent" => $this->userAgent
        ];
    }

    public function __toString(): string
    {
        return sprintf("%s %s - %s %s", $this->platform, $this->platformVersion, $this->browser, $this->browserVersion);
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function json(): string
    {
        return json_encode($this->array());
    }
}
