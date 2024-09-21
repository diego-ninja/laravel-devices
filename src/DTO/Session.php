<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Session implements JsonSerializable, Stringable
{
    public function __construct(
        public string $uuid,
        public string $ip,
        public Location $location,
        public string $status,
        public string $lastActivityAt,
        public string $startedAt,
        public string $finishedAt,
        public bool $isCurrent,
        public Device $device
    ) {
    }

    public static function fromModel(\Ninja\DeviceTracker\Models\Session $session): self
    {
        return new self(
            uuid: $session->uuid->toString(),
            ip: $session->ip,
            location: $session->location,
            status: $session->status(),
            lastActivityAt: $session->last_activity_at,
            startedAt: $session->started_at,
            finishedAt: $session->finished_at,
            isCurrent: $session->device->isCurrent(),
            device: Device::fromModel($session->device)
        );
    }

    public function array(): array
    {
        return [
            "uuid" => $this->uuid,
            "ip" => $this->ip,
            "location" => $this->location->array(),
            "status" => $this->status,
            "lastActivityAt" => $this->lastActivityAt,
            "startedAt" => $this->startedAt,
            "finishedAt" => $this->finishedAt,
            "is_current" => $this->isCurrent,
            "device" => $this->device->array()
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
