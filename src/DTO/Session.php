<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Session implements JsonSerializable, Stringable
{
    public function __construct(
        public string $id,
        public string $ip,
        public string $location,
        public string $status,
        public string $lastActivityAt,
        public string $createdAt,
        public string $updatedAt,
        public string $finishedAt,
        public Device $device
    ) {
    }

    public static function fromModel(\Ninja\DeviceTracker\Models\Session $session): self
    {
        return new self(
            id: $session->id,
            ip: $session->ip,
            location: $session->location,
            status: $session->status(),
            lastActivityAt: $session->last_activity_at,
            createdAt: $session->created_at,
            updatedAt: $session->updated_at,
            finishedAt: $session->finished_at,
            device: Device::fromModel($session->device()->first())
        );
    }

    public function array(): array
    {
        return [
            "id" => $this->id,
            "ip" => $this->ip,
            "location" => $this->location,
            "status" => $this->status,
            "lastActivityAt" => $this->lastActivityAt,
            "createdAt" => $this->createdAt,
            "updatedAt" => $this->updatedAt,
            "finishedAt" => $this->finishedAt,
            "device" => $this->device->array()
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
