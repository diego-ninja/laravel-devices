<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\FingerprintNotFoundException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

final readonly class DeviceTracker
{
    public function handle(Request $request, Closure $next)
    {
        if (DeviceManager::shouldRegenerate()) {
            DeviceManager::create();
            DeviceManager::attach();

            return $next($this->propagate($request));
        }

        if (!DeviceManager::tracked()) {
            try {
                if (config('devices.track_guest_sessions')) {
                    DeviceManager::track();
                    DeviceManager::create();
                } else {
                    $param = config('devices.device_id_request_param');
                    $uuid = DeviceIdFactory::generate();

                    $request->merge([$param => $uuid->toString()]);
                }
            } catch (DeviceNotFoundException | FingerprintNotFoundException | UnknownDeviceDetectedException $e) {
                Log::info($e->getMessage());
                return $next($request);
            }
        }

        return $next($request);
    }

    private function propagate(Request $request): Request
    {
        $param = config('devices.device_id_request_param');
        return $request->merge([$param => DeviceManager::current()?->uuid->toString()]);
    }
}
