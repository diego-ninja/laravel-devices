<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Contracts\Injector;

abstract class AbstractInjector implements Injector
{
    protected static function script(Device $device): string
    {
        $view = sprintf('laravel-devices::tracking-script.%s', static::LIBRARY_NAME);

        return view($view, [
            'current' => $device->fingerprint,
            'transport' => [
                'type' => config('devices.client_fingerprint_transport'),
                'key' => config('devices.client_fingerprint_key')
            ],
            'library' => [
                'name' => static::LIBRARY_NAME,
                'url' => static::LIBRARY_URL
            ]
        ])->render();
    }

}