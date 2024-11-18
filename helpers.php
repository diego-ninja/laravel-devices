<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Enums\Transport;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

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
        return Transport::current()->get();
    }
}

if (! function_exists('session_uuid')) {
    function session_uuid(): ?StorableId
    {
        $id = SessionFacade::get(Session::DEVICE_SESSION_ID);
        return $id ? SessionIdFactory::from($id) : null;
    }
}

if (! function_exists('session')) {
    function session(): ?Session
    {
        try {
            $id = session_uuid();
            return $id ? Session::byUuid($id) : null;
        } catch (SessionNotFoundException) {
            return null;
        }
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