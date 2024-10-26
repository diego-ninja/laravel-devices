<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector;

use Illuminate\Http\Response;

class ClientJSInjector extends AbstractInjector
{
    public const LIBRARY_NAME = 'clientjs';
    public const LIBRARY_URL = 'https://cdnjs.cloudflare.com/ajax/libs/ClientJS/0.2.1/client.min.js';

    public function inject(Response $response): Response
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