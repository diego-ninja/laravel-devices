<?php

namespace Ninja\DeviceTracker\Modules\Tracking\Cache;

use Config;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\Cache\AbstractCache;
use Ninja\DeviceTracker\Contracts\RequestAware;

final class EventTypeCache extends AbstractCache implements RequestAware
{
    public const KEY_PREFIX = 'event_type';

    private Request $request;

    protected function enabled(): bool
    {
        return in_array(self::KEY_PREFIX, Config::get('devices.cache_enabled_for', []));
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public static function key(string $key): string
    {
        $instance = self::instance();
        if (!$instance instanceof self) {
            return self::KEY_PREFIX . ':' . md5($key);
        }

        $hash = md5(sprintf(
            '%s:%s:%s',
            $instance->request()->method(),
            $instance->request()->path(),
            $instance->request()->ajax() ? 'ajax' : 'regular'
        ));

        return sprintf('%s:%s', self::KEY_PREFIX, $hash);
    }

    public static function withRequest(Request $request): ?self
    {
        $instance = self::instance();
        if ($instance instanceof self) {
            $instance->setRequest($request);
        }

        return $instance;
    }
}