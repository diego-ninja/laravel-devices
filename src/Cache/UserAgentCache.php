<?php

namespace Ninja\DeviceTracker\Cache;

use Config;

final class UserAgentCache extends AbstractCache
{
    public const KEY_PREFIX = 'ua';

    protected function enabled(): bool
    {
        return in_array(self::KEY_PREFIX, Config::get('devices.cache_enabled_for', []));
    }
}