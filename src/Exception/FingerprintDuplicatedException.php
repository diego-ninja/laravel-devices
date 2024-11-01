<?php

namespace Ninja\DeviceTracker\Exception;

use Ninja\DeviceTracker\Models\Device;

final class FingerprintDuplicatedException extends \Exception
{
    public static function forFingerprint(string $fingerprint, Device $device): self
    {
        return new self(sprintf('Fingerprint %s is already associated with device %s', $fingerprint, $device->uuid));
    }
}
