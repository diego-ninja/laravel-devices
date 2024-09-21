<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;
use Ramsey\Uuid\UuidInterface;

final class DeviceNotFoundException extends Exception
{
    public static function withDevice(UuidInterface $uuid): self
    {
        return new self("Device with id {$uuid->toString()} not found");
    }
}
