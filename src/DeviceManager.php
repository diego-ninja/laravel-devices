<?php

namespace Ninja\DeviceTracker;

use Auth;
use Config;
use Cookie;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Contracts\DeviceDetector;
use Ninja\DeviceTracker\Models\Device;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class DeviceManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isUserDevice(UuidInterface $deviceUuid): bool
    {
        return Auth::user()?->hasDevice($deviceUuid);
    }

    public function addUserDevice(Request $request): bool
    {
        $deviceUuid = self::deviceUuid();
        if ($deviceUuid) {
            if (Auth::user()?->hasDevice($deviceUuid)) {
                return true;
            }

            $device = Device::register(
                deviceUuid: $deviceUuid,
                data: app(DeviceDetector::class)->detect($request),
                user: Auth::user(),
            );

            return Auth::user()?->addDevice($device);
        }

        return false;
    }

    public function userDevices(): Collection
    {
        return Auth::user()?->devices;
    }

    public function deviceUuid(): ?UuidInterface
    {
        return Device::getDeviceUuid();
    }

    public function tracked(): bool
    {
        return Cookie::has(Config::get('devices.device_id_cookie_name'));
    }

    public function track(): UuidInterface
    {
        $deviceUuid = Uuid::uuid7();
        Cookie::queue(
            Cookie::forever(
                name: Config::get('devices.device_id_cookie_name'),
                value: $deviceUuid->toString(),
                secure: Config::get('session.secure', false),
                httpOnly: Config::get('session.http_only', true)
            )
        );

        return $deviceUuid;
    }
}
