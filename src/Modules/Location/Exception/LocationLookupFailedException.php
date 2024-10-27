<?php

namespace Ninja\DeviceTracker\Modules\Location\Exception;

use Exception;
use Throwable;

final class LocationLookupFailedException extends Exception
{
    public static function forIp(string $ip, ?Throwable $previous): self
    {
        return new self(
            message: sprintf("Failed to lookup location for IP: %s", $ip),
            previous: $previous
        );
    }
}
