<?php

use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?string
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            if (request()->hasHeader(Config::get('devices.client_fingerprint_key'))) {
                return request()->header(Config::get('devices.client_fingerprint_key'));
            }

            if (Cookie::has(Config::get('devices.client_fingerprint_key'))) {
                return Cookie::get(Config::get('devices.client_fingerprint_key'));
            }

            throw new RuntimeException('Fingerprinting is enabled but no fingerprint was found in request');
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
