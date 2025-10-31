<?php

namespace Ninja\DeviceTracker\Factories;

final class SessionIdFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return config(
            'devices.transports.session_id.storable_class',
            config('devices.session_id_storable_class'),
        );
    }
}
