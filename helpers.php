<?php

use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DeviceManager;
use Ninja\DeviceTracker\Exception\FingerprintNotFoundException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Models\Session;

if (! function_exists('fingerprint')) {
    /**
     * @throws FingerprintNotFoundException
     */
    function fingerprint(): ?string
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            if (Config::get('devices.client_fingerprint_transport') === 'cookie') {
                return Cookie::get(Config::get('devices.client_fingerprint_key'));
            }

            if (Config::get('devices.client_fingerprint_transport') === 'header') {
                return request()->header(Config::get('devices.client_fingerprint_key'));
            }

            throw FingerprintNotFoundException::create();
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
