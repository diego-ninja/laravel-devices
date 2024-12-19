<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Enums\SessionTransport;
use Ninja\DeviceTracker\Models\Device;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?string
    {
        if (config('devices.fingerprinting_enabled') === true) {
            $cookie = Config::get('devices.fingerprint_id_cookie_name');

            $fingerprint = Cookie::get($cookie);
            if (is_string($fingerprint)) {
                return $fingerprint;
            }
        }

        return null;
    }
}

if (! function_exists('device_uuid')) {
    function device_uuid(): ?StorableId
    {
        return DeviceTransport::current()?->get();
    }
}

if (! function_exists('session_uuid')) {
    function session_uuid(): ?StorableId
    {
        return SessionTransport::current()?->get();
    }
}

if (! function_exists('device')) {
    function device(bool $cached = true): ?Device
    {

        if (config('devices.fingerprinting_enabled') === true) {
            $fingerprint = fingerprint();
            if ($fingerprint !== null) {
                return Device::byFingerprint($fingerprint, $cached);
            }
        }

        $id = device_uuid();
        if ($id === null) {
            return null;
        }

        return Device::byUuid($id, $cached);
    }
}

if (! function_exists('guard')) {
    function guard(): Guard
    {
        return auth(config('devices.auth_guard'));
    }
}

if (! function_exists('user')) {
    function user(): ?Authenticatable
    {
        return guard()->hasUser() ? guard()->user() : null;
    }
}
