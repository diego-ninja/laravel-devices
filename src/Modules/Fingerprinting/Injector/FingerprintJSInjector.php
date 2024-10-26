<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector;

use Illuminate\Http\Response;
use Ninja\DeviceTracker\Facades\DeviceManager;

class FingerprintJSInjector extends AbstractInjector
{
    public const LIBRARY_NAME = 'fingerprintjs';
    public const LIBRARY_URL = 'https://openfpcdn.io/fingerprintjs/v4';

    public static function inject(Response $response): Response
    {
        $content = $response->getContent();

        $device = DeviceManager::current();
        if ($device) {
            $script = self::script($device);
            $response->setContent(str_replace('</head>', $script . '</head>', $content));
        }

        return $response;
    }
}
