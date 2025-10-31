<?php

namespace Ninja\DeviceTracker\Factories;

final class FingerprintFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return config(
            'devices.transports.fingerprint.storable_class',
            config('devices.fingerprint_storable_class'),
        );
    }
}
