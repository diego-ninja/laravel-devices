<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Enums\SessionTransport;
use Ninja\DeviceTracker\Models\Device;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?string
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            $cookie = Config::get('devices.fingerprint_id_cookie_name');

            return Cookie::has($cookie) ? Cookie::get($cookie) : null;
        }

        return null;
    }
}

if (! function_exists('device_uuid')) {
    function device_uuid(): ?StorableId
    {
        return DeviceTransport::current()->get();
    }
}

if (! function_exists('session_uuid')) {
    function session_uuid(): ?StorableId
    {
        return SessionTransport::current()->get();
    }
}

if (! function_exists('device')) {
    function device(bool $cached = true): ?Device
    {

        if (Config::get('devices.fingerprinting_enabled')) {
            $fingerprint = fingerprint();
            if ($fingerprint) {
                return Device::byFingerprint($fingerprint, $cached);
            }
        }

        $id = device_uuid();

        return $id ? Device::byUuid($id, $cached) : null;
    }
}
