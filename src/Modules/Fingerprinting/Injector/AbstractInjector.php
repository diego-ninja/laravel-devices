<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector;

use Illuminate\Http\Response;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Contracts\Injector;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Enums\Library;

abstract class AbstractInjector implements Injector
{
    public const LIBRARY_NAME = '';
    public const LIBRARY_URL = '';
    protected static function script(Device $device): string
    {
        $view = sprintf('laravel-devices::%s-tracking-script', static::LIBRARY_NAME);

        return view($view, [
            'current' => $device->fingerprint,
            'transport' => [
                'type' => config('devices.client_fingerprint_transport'),
                'key' => config('devices.client_fingerprint_key'),
            ],
            'library' => [
                'name' => static::LIBRARY_NAME,
                'url' => static::LIBRARY_URL,
            ],
        ])->render();
    }

    public function inject(Response $response): Response
    {
        $content = $response->getContent();

        $device = DeviceManager::current();
        if ($device) {
            $script = self::script($device);
            $response->setContent(str_replace('</head>', $script.'</head>', $content));
        }

        return $response;
    }

    public static function library(): Library
    {
        return Library::from(static::LIBRARY_NAME);
    }
}
