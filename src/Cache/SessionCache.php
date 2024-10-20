<?php

namespace Ninja\DeviceTracker\Cache;

use Config;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Session;

final class SessionCache extends AbstractCache
{
    public const KEY_PREFIX = 'session';

    protected function enabled(): bool
    {
        return in_array(self::KEY_PREFIX, Config::get('devices.cache_enabled_for', []));
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        if (!$item instanceof Session) {
            throw new InvalidArgumentException('Item must be an instance of Session');
        }

        $this->cache->forget($item->key());
        $this->cache->forget("user:sessions:" . $item->device->id);
    }

    public static function userSessions(Authenticatable $user)
    {
        if (!self::instance()->enabled()) {
            return $user->sessions()->with('device')->get();
        }

        return self::remember('user:sessions:' . $user->id, function () use ($user) {
            return $user->sessions()->with('device')->get();
        });
    }

    public function activeSessions(Authenticatable $user)
    {
        if (!self::instance()->enabled()) {
            return $user->sessions()->with('device')->active();
        }

        return self::remember('user:sessions:active:' . $user->id, function () use ($user) {
            return $user->sessions()->with('device')->active();
        });
    }
}
