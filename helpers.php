<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Transports\DeviceTransport;
use Ninja\DeviceTracker\Transports\FingerprintTransport;
use Ninja\DeviceTracker\Transports\SessionTransport;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?StorableId
    {
        return FingerprintTransport::currentId();
    }
}

if (! function_exists('device_uuid')) {
    function device_uuid(): ?StorableId
    {
        return DeviceTransport::currentId();
    }
}

if (! function_exists('session_uuid')) {
    function session_uuid(): ?StorableId
    {
        return SessionTransport::currentId();
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

if (! function_exists('device_session')) {
    function device_session(bool $cached = true): ?Session
    {
        $sessionId = session_uuid();
        if ($sessionId === null) {
            return null;
        }

        $session = Session::byUuid($sessionId);
        if ($session === null) {
            return null;
        }

        return $session;
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
        return auth()->check() ? auth()->user() : null;
    }
}
