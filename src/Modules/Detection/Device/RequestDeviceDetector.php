<?php

namespace Ninja\DeviceTracker\Modules\Detection\Device;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Device;
use Ninja\DeviceTracker\Modules\Detection\Contracts\DeviceDetectorInterface;

final class RequestDeviceDetector implements DeviceDetectorInterface
{
    public function __construct() {}

    public function detect(Request|string $request, ?Device $base = null): ?Device
    {
        if (is_string($request) || $base === null) {
            return null;
        }

        $advertisingId = $request->input(
            config('devices.device_identifiers_parameters.advertising_id', 'advertising_id'),
        );
        $deviceId = $request->input(
            config('devices.device_identifiers_parameters.device_id', 'device_id'),
        );

        $base->advertisingId ??= $advertisingId;
        $base->deviceId ??= $deviceId;

        return $base;
    }
}
