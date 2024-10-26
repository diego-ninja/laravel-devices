<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Cookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Models\Device;

final readonly class DeviceTracker
{
    public function handle(Request $request, Closure $next)
    {
        if (!DeviceManager::tracked()) {
            $deviceUuid = DeviceManager::track();
            Log::info('Device not found, creating new one with id ' . $deviceUuid->toString());
        }

        //TODO: This is a hack to make the device available in the request
        \Ninja\DeviceTracker\DeviceManager::$deviceUuid = DeviceManager::current()->uuid;

        $response = $next($request);

        if (\Config::get("devices.fingerprinting_enabled")) {
            if (!$this->html($response)) {
                return $response;
            }

            if (!Cookie::has('fingerprint')) {
                $content = $response->getContent();
                $response->setContent($this->inject($content));
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
        return view('fingerprinting::tracking-script', [
            'fingerprint' => $device->fingerprint,
        ])->render();
    }

    private function inject(string $html): string
    {
        $device = DeviceManager::current();
        if ($device) {
            $script = $this->script($device);
            str_replace('</head>', $script . '</head>', $html);
        }

        return $html;
    }
}
