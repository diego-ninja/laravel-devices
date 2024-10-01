<?php

namespace Ninja\DeviceTracker\Cache;

use Config;

final class UserAgentCache extends AbstractCache
{
    protected function enabled(): bool
    {
        return in_array('user-agent', Config::get('devices.cache_enabled_for', []));
    }
}