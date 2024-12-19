<?php

namespace Ninja\DeviceTracker\Cache;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Traits\HasDevices;

final class DeviceCache extends AbstractCache
{
    public const KEY_PREFIX = 'device';

    protected function enabled(): bool
    {
        $enabled = Config::get('devices.cache_enabled_for', []);
        if (! is_array($enabled)) {
            return false;
        }

        return in_array(self::KEY_PREFIX, $enabled, true);
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (! $item instanceof Device) {
            throw new InvalidArgumentException('Item must be an instance of Device');
        }

        $this->cache?->forget($item->key());
        $item->users()->each(fn (Authenticatable $user) => $this->cache?->forget(sprintf('user:devices:%s', $user->getAuthIdentifier())));
    }

    /**
     * @return Collection<int, Device>|null
     */
    public static function userDevices(Authenticatable $user): ?Collection
    {
        $uses = in_array(HasDevices::class, class_uses($user), true);
        if (! $uses) {
            throw new InvalidArgumentException('User must use HasDevices trait');
        }

        if (! self::instance()->enabled()) {
            return $user->devices;
        }

        /** @var Collection<int, Device> $devices */
        $devices = self::remember(sprintf('user:devices:%s', $user->getAuthIdentifier()), function () use ($user) {
            return $user->devices;
        });

        return $devices;
    }
}
