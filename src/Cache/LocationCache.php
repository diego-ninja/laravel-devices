<?php

namespace Ninja\DeviceTracker\Cache;

use Illuminate\Support\Facades\Config;

final class LocationCache extends AbstractCache
{
    public const KEY_PREFIX = 'location';

    protected function enabled(): bool
    {
        return in_array(self::KEY_PREFIX, Config::get('devices.cache_enabled_for', []), true);
    }
}
