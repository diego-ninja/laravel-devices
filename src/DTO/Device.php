<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Device implements JsonSerializable, Stringable
{
    public function __construct(
        public Browser $browser,
        public Platform $platform,
        public DeviceType $device,
        public string $ip,
        public ?string $grade,
        public ?string $userAgent
    ) {
    }
    public static function fromModel(\Ninja\DeviceTracker\Models\Device $device): self
    {
        return new self(
            browser: Browser::fromArray([
                "name" => $device->browser,
                "version" => Version::fromString($device->browser_version),
                "family" => $device->browser_family,
                "engine" => $device->browser_engine
            ]),
            platform: Platform::fromArray([
                "name" => $device->platform,
                "version" => Version::fromString($device->platform_version),
                "family" => $device->platform_family
            ]),
            device: DeviceType::fromArray([
                "family" => $device->device,
                "model" => $device->device_model,
                "type" => $device->device_type
            ]),
            ip: $device->ip,
            grade: $device->grade,
            userAgent: $device->source
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            browser: Browser::fromArray($data['browser']),
            platform: Platform::fromArray($data['platform']),
            device: DeviceType::fromArray($data['device']),
            ip: $data['ip_address'],
            grade: $data['grade'],
            userAgent: $data['user_agent']
        );
    }

    public function array(): array
    {
        return [
            "browser" => $this->browser->array(),
            "platform" => $this->platform->array(),
            "device" => $this->device->array(),
            "ip_address" => $this->ip,
            "grade" => $this->grade,
            "user_agent" => $this->userAgent,
            "label" => (string) $this
        ];
    }

    public function __toString(): string
    {
        return sprintf("%s at %s on %s", $this->browser, $this->device, $this->platform);
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
