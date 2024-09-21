<?php

namespace Ninja\DeviceTracker;

use Auth;
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
        return Device::isUserDevice($uuid);
    }

    public function deleteDevice($id): int
    {
        return Device::destroy($id);
    }

    public function addUserDevice(?string $userAgent = null): bool
    {
        return Auth::user()?->addDevice($userAgent);
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
