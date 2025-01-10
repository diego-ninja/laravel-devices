<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\FingerprintNotFoundException;
use Ninja\DeviceTracker\Exception\InvalidDeviceDetectedException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

final readonly class DeviceTracker
{
    /**
     * @throws UnknownDeviceDetectedException
     * @throws InvalidDeviceDetectedException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $detectedDevice = DeviceManager::detect();
        if (! $detectedDevice || ! DeviceManager::isWhitelisted($detectedDevice->source)) {
            if (! $detectedDevice || $detectedDevice->unknown()) {
                $this->isDeviceAllowed(
                    userAgent: $detectedDevice->source ?? null,
                );
            } elseif ($detectedDevice->bot()) {
                $this->isDeviceAllowed(
                    unknown: false,
                    userAgent: $detectedDevice->source,
                );
            }
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

                $this->isDeviceAllowed(userAgent: $detectedDevice->source);

                return $next($request);
            }
        }

        $deviceUuid = device_uuid();
        if ($deviceUuid === null) {
            $this->isDeviceAllowed(userAgent: $detectedDevice->source);
            return $next($request);
        }

        return DeviceTransport::set($next(DeviceTransport::propagate($deviceUuid)), $deviceUuid);
    }

    /**
     * @throws UnknownDeviceDetectedException
     * @throws InvalidDeviceDetectedException
     */
    private function isDeviceAllowed(bool $unknown = true, ?string $userAgent = null): void
    {
        if (isset($userAgent) && DeviceManager::isWhitelisted($userAgent)) {
            return;
        }
        if (Config::get('devices.allow_' . ($unknown ? 'unknown' : 'bot') . '_devices', false) === false) {
            if (Config::get('devices.middlewares.device-tracker.exception_on_invalid_devices', false) === false) {
                abort(403, sprintf(
                    '%s device detected: user-agent %s',
                    $unknown ? 'Unknown' : 'Bot',
                    $userAgent
                ));
            } else {
                if ($unknown === true) {
                    throw UnknownDeviceDetectedException::withUA($userAgent);
                }
                throw InvalidDeviceDetectedException::withUA($userAgent);
            }
        }
    }
}
