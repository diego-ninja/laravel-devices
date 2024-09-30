<?php

namespace Ninja\DeviceTracker\ValueObject;

use Ninja\DeviceTracker\Contracts\StorableId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class DeviceId implements StorableId
{
    private UuidInterface $id;

    private function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    public static function from(StorableId|string $id): StorableId
    {
        if ($id instanceof StorableId) {
            return $id;
        }

        return new self(Uuid::fromString($id));
    }

    public static function build(): self
    {
        return new self(Uuid::uuid7());
    }

    public function toString(): string
    {
        return $this->id->toString();
    }

    public function equals(StorableId $other): bool
    {
        return $this->id->equals($other->id);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
