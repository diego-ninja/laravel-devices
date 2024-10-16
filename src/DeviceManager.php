<?php

namespace Ninja\DeviceTracker;

use Auth;
use Config;
use Cookie;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Contracts\DeviceDetector;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;
use RuntimeException;

final class DeviceManager
{
    public Application $app;

    public static StorableId $deviceUuid;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isUserDevice(StorableId $deviceUuid): bool
    {
        return Auth::user()?->hasDevice($deviceUuid);
    }

    public function addUserDevice(Request $request): bool
    {
        $deviceUuid = device_uuid();
        if ($deviceUuid) {
            if (Auth::user()?->hasDevice($deviceUuid)) {
                return true;
            }

            $device = Device::register(
                deviceUuid: $deviceUuid,
                data: app(DeviceDetector::class)->detect($request),
                user: Auth::user()
            );

            return Auth::user()?->addDevice($device);
        }

        return false;
    }

    public function userDevices(): Collection
    {
        return Auth::user()?->devices;
    }

    public function tracked(): bool
    {
        return Cookie::has(Config::get('devices.device_id_cookie_name'));
    }

    public function track(): StorableId
    {
        self::$deviceUuid = DeviceIdFactory::generate();
        Cookie::queue(
            Cookie::forever(
                name: Config::get('devices.device_id_cookie_name'),
                value: self::$deviceUuid->toString(),
                secure: Config::get('session.secure', false),
                httpOnly: Config::get('session.http_only', true)
            )
        );

        DeviceTrackedEvent::dispatch(self::$deviceUuid);

        return self::$deviceUuid;
    }

    public function current(): ?Device
    {
        return Device::current();
    }
}
