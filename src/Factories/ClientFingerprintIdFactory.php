<?php

namespace Ninja\DeviceTracker\Factories;

final class ClientFingerprintIdFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return config('devices.client_fingerprint_storable_class');
    }
}
