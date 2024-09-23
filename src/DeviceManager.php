<?php

namespace Ninja\DeviceTracker;

use Auth;
use Config;
use Cookie;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Models\Device;
use Ramsey\Uuid\UuidInterface;

final readonly class DeviceManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isUserDevice(UuidInterface $uuid): bool
    {
        $device = Device::findByUuid($uuid);
        return $device->user_id === Auth::id();
    }

    public function addUserDevice(?string $userAgent = null): bool
    {
        $cookieName = Config::get('devices.device_id_cookie_name');
        if (Cookie::has($cookieName)) {
            if (Auth::user()?->hasDevice(Device::getDeviceUuid())) {
                return true;
            }

            $device = Device::register($userAgent, Auth::user());
            return Auth::user()?->addDevice($device);
        }

        return false;
    }

    public function getUserDevices(): Collection
    {
        return Auth::user()?->devices;
    }

    public function getDeviceUuid(): ?UuidInterface
    {
        return Device::getDeviceUuid();
    }
}
