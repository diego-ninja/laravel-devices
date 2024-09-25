<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Facades\DeviceManager;

final readonly class DeviceTrack
{
    public function handle(Request $request, Closure $next)
    {
        if (!DeviceManager::tracked()) {
            $deviceUuid = DeviceManager::track();
            Log::info('Device not found, creating new one with id ' . $deviceUuid->toString());
        }

        return $next($request);
    }
}
