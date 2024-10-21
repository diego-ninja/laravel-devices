<?php

use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?string
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            if (Config::get('devices.client_fingerprint_transport') === 'cookie') {
                return Cookie::get(Config::get('devices.client_fingerprint_key'));
            }

            if (Config::get('devices.client_fingerprint_transport') === 'header') {
                return request()->header(Config::get('devices.client_fingerprint_key'));
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
