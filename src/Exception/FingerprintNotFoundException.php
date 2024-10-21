<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;

final class FingerprintNotFoundException extends Exception
{
    public static function create(): self
    {
        return new self('Fingerprinting is enabled but no fingerprint was found in request');
    }
}
