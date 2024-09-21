<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;
use Ramsey\Uuid\UuidInterface;

final class SessionNotFoundException extends Exception
{
    public static function withSession(UuidInterface $sessionId): self
    {
        return new self("Session with id {$sessionId->toString()} not found");
    }
}
