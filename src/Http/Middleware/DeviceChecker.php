<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;

final readonly class DeviceChecker
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (is_null(device())) {
            if (Config::get('devices.middlewares.device-checker.exception_on_unavailable_devices', false) === false) {
                abort(403, 'Device not found.');
            } else {
                throw new DeviceNotFoundException();
            }
        }

        return $next($request);
    }
}
