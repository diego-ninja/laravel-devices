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

        // TODO: Define custom parameters for both advertisingId and deviceId
        $advertisingId = $request->input('advertising_id');
        $deviceId = $request->input('device_id');
        $clientFingerprint = $request->input('client_fingerprint');

        $base->advertisingId ??= $advertisingId;
        $base->deviceId ??= $deviceId;
        $base->clientFingerprint ??= $clientFingerprint;

        return $base;
    }
}
