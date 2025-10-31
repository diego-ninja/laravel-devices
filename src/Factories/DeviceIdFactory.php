<?php

namespace Ninja\DeviceTracker\Factories;

final class DeviceIdFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return config(
            'devices.transports.device_id.storable_class',
            config('devices.device_id_storable_class'),
        );
    }
}
