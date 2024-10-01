<?php

namespace Ninja\DeviceTracker\Cache;

use Config;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Device;

final class DeviceCache extends AbstractCache
{
    protected function enabled(): bool
    {
        return in_array('device', Config::get('devices.cache_enabled_for', []));
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        if (!$item instanceof Device) {
            throw new InvalidArgumentException('Item must be an instance of Device');
        }

        $this->cache->forget($item->key());
        $this->cache->forget("user:devices:" . $item->user->id);
    }

    public static function userDevices(Authenticatable $user)
    {
        if (!self::instance()->enabled()) {
            return $user->devices;
        }

        return self::remember('user:devices:' . $user->id, function () use ($user) {
            return $user->devices;
        });
    }
}
