<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;
use Ninja\DeviceTracker\Contracts\StorableId;

final class DeviceNotFoundException extends Exception
{
    public static function withDevice(StorableId|string $uuid): self
    {
        return new self(sprintf('Device with UUID %s not found', $uuid));
    }
}
