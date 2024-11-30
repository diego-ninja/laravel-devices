<?php

namespace Ninja\DeviceTracker\Cache;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Session;

final class SessionCache extends AbstractCache
{
    public const KEY_PREFIX = 'session';

    protected function enabled(): bool
    {
        return in_array(self::KEY_PREFIX, Config::get('devices.cache_enabled_for', []), true);
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (! $item instanceof Session) {
            throw new InvalidArgumentException('Item must be an instance of Session');
        }

        $this->cache?->forget($item->key());
        $this->cache?->forget(sprintf('user:sessions:%s', $item->device->id));
    }

    /**
     * @return Collection<int, Session>|null
     */
    public static function userSessions(Authenticatable $user): ?Collection
    {
        if (! self::instance()->enabled()) {
            return $user->sessions()->with('device')->get();
        }

        return self::remember('user:sessions:'.$user->getAuthIdentifier(), function () use ($user) {
            return $user->sessions()->with('device')->get();
        });
    }

    /**
     * @return Collection<int, Session>|null
     */
    public static function activeSessions(Authenticatable $user): ?Collection
    {
        if (! self::instance()->enabled()) {
            return $user->sessions()->with('device')->active();
        }

        return self::remember('user:sessions:active:'.$user->getAuthIdentifier(), function () use ($user) {
            return $user->sessions()->with('device')->active();
        });
    }
}
