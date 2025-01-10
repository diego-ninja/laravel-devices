<?php

namespace Ninja\DeviceTracker\Exception;

final class InvalidDeviceDetectedException extends \Exception
{
    public function __construct(string $message = 'Invalid device detected', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function withUA(?string $ua): self
    {
        return new self(sprintf("Unknown device detected with user agent: %s", $ua ?? 'undefined user-agent'));
    }
}
