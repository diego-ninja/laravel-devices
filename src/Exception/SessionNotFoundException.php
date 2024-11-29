<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;
use Ninja\DeviceTracker\Contracts\StorableId;

final class SessionNotFoundException extends Exception
{
    public static function withSession(StorableId $sessionId): self
    {
        return new self(sprintf('Session with UUID %s not found', $sessionId));
    }
}
