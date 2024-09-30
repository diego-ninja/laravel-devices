<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;
use Ninja\DeviceTracker\Contracts\StorableId;

final class DeviceNotFoundException extends Exception
{
    public static function withDevice(StorableId $uuid): self
    {
        return new self("Device with id {$uuid->toString()} not found");
    }
}
