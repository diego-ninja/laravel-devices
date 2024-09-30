<?php

namespace Ninja\DeviceTracker\Factories;

final class SessionIdFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return config('devices.session_id_storable_class');
    }
}
