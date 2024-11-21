<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Enums\DeviceTransport;
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

            return $next(DeviceTransport::propagate(device_uuid()));
        }

        if (! DeviceManager::tracked()) {
            try {
                if (config('devices.track_guest_sessions')) {
                    DeviceManager::track();
                    DeviceManager::create();
                } else {
                    DeviceTransport::propagate(DeviceIdFactory::generate());
                }
            } catch (DeviceNotFoundException|FingerprintNotFoundException|UnknownDeviceDetectedException $e) {
                Log::info($e->getMessage());

                return $next($request);
            }
        }

        return DeviceTransport::set($next(DeviceTransport::propagate(device_uuid())), device_uuid());
    }
}
