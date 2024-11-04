<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Contracts\StorableId;
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

            return $next($this->propagate($request, device_uuid()));
        }

        if (!DeviceManager::tracked()) {
            try {
                if (config('devices.track_guest_sessions')) {
                    $this->propagate($request, DeviceManager::track());
                    DeviceManager::create();
                } else {
                    $this->propagate($request, DeviceIdFactory::generate());
                }
            } catch (DeviceNotFoundException | FingerprintNotFoundException | UnknownDeviceDetectedException $e) {
                Log::info($e->getMessage());
                return $next($request);
            }
        }

        return $next($this->propagate($request, device_uuid()));
    }

    private function propagate(Request $request, StorableId $deviceUuid): Request
    {
        $param = config('devices.device_id_request_param');
        return $request->merge([$param => $deviceUuid->toString()]);
    }
}
