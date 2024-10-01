<?php

namespace Ninja\DeviceTracker\Cache;

use Config;

final class LocationCache extends AbstractCache
{
    protected function enabled(): bool
    {
        return in_array('location', Config::get('devices.cache_enabled_for', []));
    }
}