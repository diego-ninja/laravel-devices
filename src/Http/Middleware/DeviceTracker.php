<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Facades\DeviceManager;

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

        return $next($request);
    }
}
