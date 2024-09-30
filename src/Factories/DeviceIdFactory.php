<?php

namespace Ninja\DeviceTracker\Factories;

use Illuminate\Support\Facades\Config;

final class DeviceIdFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return Config::get('devices.device_id_storable_class');
    }
}
