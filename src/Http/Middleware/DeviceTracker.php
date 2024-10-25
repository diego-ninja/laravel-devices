<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Services\FingerprintingService;

final readonly class DeviceTracker
{
    public function __construct(private FingerprintingService $service)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!DeviceManager::tracked()) {
            $deviceUuid = DeviceManager::track();
            Log::info('Device not found, creating new one with id ' . $deviceUuid->toString());
        }

        //TODO: This is a hack to make the device available in the request
        \Ninja\DeviceTracker\DeviceManager::$deviceUuid = DeviceManager::current()->uuid;

        $this->fingerprint(DeviceManager::current());

        return $next($request);
    }

    private function fingerprint(Device $device): void
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            if (!$device->fingerprint) {
                $fingerprint = $this->service->identify($$device);
                if ($fingerprint) {
                    $device->fingerprint = $fingerprint;
                    $device->save();
                }
            }
        }
    }
}
