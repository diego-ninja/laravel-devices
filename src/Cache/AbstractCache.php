<?php

namespace Ninja\DeviceTracker\Cache;

use Config;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Device;
use Psr\SimpleCache\InvalidArgumentException;

abstract class AbstractCache
{
    protected static ?self $instance = null;

    protected ?Repository $cache = null;

    private function __construct()
    {
        if (!$this->enabled()) {
            return;
        }

        $this->cache = Cache::store(Config::get('devices.cache_store'));
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function get(string $key): ?Device
    {
        return self::instance()->getItem($key);
    }

    public static function put(Cacheable $item): void
    {
        self::instance()->putItem($item);
    }

    public static function remember(string $key, callable $callback): mixed
    {
        return self::instance()->cache->remember($key, Config::get('devices.cache_ttl'), $callback);
    }

    public static function forget(Cacheable $item): void
    {
        self::instance()->forgetItem($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getItem(string $key): ?Device
    {
        if (!$this->enabled()) {
            return null;
        }

        return $this->cache->get($key);
    }

    protected function putItem(Cacheable $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        $this->cache->put($item->key(), $item, $item->ttl());
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        $this->cache->forget($item->key());
    }

    abstract protected function enabled(): bool;
}
