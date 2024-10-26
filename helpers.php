<?php

use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Models\Session;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?string
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            $cookie = Cookie::get(Config::get('devices.fingerprint_id_cookie_name'));
            return Cookie::has($cookie) ? Cookie::get($cookie) : null;
        }

        return null;
    }
}

if (! function_exists('device_uuid')) {
    function device_uuid(): ?StorableId
    {
        $cookieName = Config::get('devices.device_id_cookie_name');
        return Cookie::has($cookieName) ? DeviceIdFactory::from(Cookie::get($cookieName)) : DeviceManager::$deviceUuid;
    }
}

if (! function_exists('session_uuid')) {
    function session_uuid(): ?StorableId
    {
        $id = SessionFacade::get(Session::DEVICE_SESSION_ID);
        return $id ? SessionIdFactory::from($id) : null;
    }
}
