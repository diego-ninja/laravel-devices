<?php

namespace Ninja\DeviceTracker;

use Auth;
use Config;
use Cookie;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Contracts\DeviceDetector;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\FingerprintNotFoundException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;

final class DeviceManager
{
    public Application $app;

    public static ?StorableId $deviceUuid = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isUserDevice(StorableId $deviceUuid): bool
    {
        return Auth::user()?->hasDevice($deviceUuid);
    }

    public function attach(?StorableId $deviceUuid = null): bool
    {
        $deviceUuid = $deviceUuid ?? device_uuid();
        if ($deviceUuid) {
            if (Auth::user()?->hasDevice($deviceUuid)) {
                return true;
            }

            Auth::user()?->devices()->attach($deviceUuid);
            return true;
        }

        return false;
    }

    public function userDevices(): Collection
    {
        return Auth::user()?->devices;
    }

    public function tracked(): bool
    {
        return device_uuid() && Device::exists(device_uuid());
    }

    public function fingerprinted(): bool
    {
        return fingerprint() && Device::byFingerprint(fingerprint());
    }

    /**
     * @throws DeviceNotFoundException
     * @throws FingerprintNotFoundException
     */
    public function track(): StorableId
    {
        if (device_uuid()) {
            if (Config::get('devices.regenerate_devices')) {
                self::$deviceUuid = device_uuid();
            } else {
                throw new DeviceNotFoundException('Tracked device not found in database');
            }
        } else {
            self::$deviceUuid = DeviceIdFactory::generate();
            Cookie::queue(
                Cookie::forever(
                    name: Config::get('devices.device_id_cookie_name'),
                    value: self::$deviceUuid->toString(),
                    secure: Config::get('session.secure', false),
                    httpOnly: Config::get('session.http_only', true)
                )
            );
        }

        Device::register(
            deviceUuid: self::$deviceUuid,
            data: app(DeviceDetector::class)->detect(\request())
        );

        event(new DeviceTrackedEvent(self::$deviceUuid));

        return self::$deviceUuid;
    }

    public function current(): ?Device
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            return Device::byFingerprint(fingerprint());
        }

        return Device::byUuid(device_uuid());
    }
}
