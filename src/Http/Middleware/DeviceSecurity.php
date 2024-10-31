<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Security\DeviceSecurityManager;

final readonly class DeviceSecurity
{
    public function __construct(private DeviceSecurityManager $manager)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!config('devices.security.enabled')) {
            return $next($request);
        }

        if (!session()) {
            return $next($request);
        }

        $device = device();

        $assessment = $this->manager->assess($device);
        $this->manager->handle($device, $assessment);
    }
}

