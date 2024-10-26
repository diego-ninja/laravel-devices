<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Models\Device;

class FingerprintTracker
{
    public function handle(Request $request, Closure $next)
    {
        if (!config("devices.fingerprinting_enabled")) {
            return $next($request);
        }

        $response = $next($request);

        if (!DeviceManager::fingerprinted()) {
            return $this->addFingerprint($response);
        }

        return $response;
    }

    private function addFingerprint(Response $response): Response
    {
        $clientCookie = Config::get('devices.client_fingerprint_key');
        $serverCookie = Config::get('devices.fingerprint_id_cookie_name');

        if (!$this->html($response)) {
            return $response;
        }

        if (!isset($_COOKIE[$clientCookie])) {
            $content = $response->getContent();
            $response->setContent($this->inject($content));

            return $response;
        } else {
            $device = DeviceManager::current();
            if ($device->fingerprint === null) {
                $device->fingerprint = $_COOKIE[$clientCookie];
                $device->save();

                if (!Cookie::has($serverCookie)) {
                    Cookie::queue(
                        Cookie::forever(
                            name:$serverCookie,
                            value: $device->fingerprint,
                            secure: Config::get('session.secure', false),
                            httpOnly: Config::get('session.http_only', true)
                        )
                    );
                }
            }
        }

        return $response;
    }

    private function html(mixed $response): bool
    {
        if (!$response instanceof Response) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');
        if (!$contentType || !str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }

    private function script(Device $device): string
    {
        return view('laravel-devices::tracking-script', [
            'current' => $device->fingerprint,
            'transport' => [
                'type' => config('devices.client_fingerprint_transport'),
                'key' => config('devices.client_fingerprint_key')
            ]
        ])->render();
    }

    private function inject(string $html): string
    {
        $device = DeviceManager::current();
        if ($device) {
            $script = $this->script($device);
            return str_replace('</head>', $script . '</head>', $html);
        }

        return $html;
    }

}