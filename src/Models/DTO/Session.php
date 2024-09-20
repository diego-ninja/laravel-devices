<?php

namespace Ninja\DeviceTracker\Models\DTO;

final readonly class Session
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
}