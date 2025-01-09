<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\FingerprintNotFoundException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Modules\Detection\Contracts\DeviceDetector;

final readonly class DeviceTracker
{
    /**
     * @throws UnknownDeviceDetectedException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $detectedDevice = app(DeviceDetector::class)->detect(request());
        if (! $detectedDevice || $detectedDevice->unknown()) {
            $this->checkUnknownDevices();
        }

        if (DeviceManager::shouldRegenerate()) {
            DeviceManager::create();
            DeviceManager::attach();

            return $next(DeviceTransport::propagate(device_uuid()));
        }

        if (! DeviceManager::tracked()) {
            try {
                if (config('devices.track_guest_sessions') === true) {
                    DeviceManager::track();
                    DeviceManager::create();
                } else {
                    DeviceTransport::propagate(DeviceIdFactory::generate());
                }
            } catch (DeviceNotFoundException|FingerprintNotFoundException|UnknownDeviceDetectedException $e) {
                Log::info($e->getMessage());

                $this->checkUnknownDevices();

                return $next($request);
            }
        }

        $deviceUuid = device_uuid();
        if ($deviceUuid === null) {
            $this->checkUnknownDevices();
            return $next($request);
        }

        return DeviceTransport::set($next(DeviceTransport::propagate($deviceUuid)), $deviceUuid);
    }

    /**
     * @throws UnknownDeviceDetectedException
     */
    private function checkUnknownDevices(): void
    {
        if (Config::get('devices.allow_unknown_devices', false) === false) {
            if (Config::get('devices.middlewares.device-tracker.exception_on_unknown_devices', false) === false) {
                abort(403, 'Unknown device detected');
            } else {
                throw new UnknownDeviceDetectedException();
            }
        }
    }
}
