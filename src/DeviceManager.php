<?php

namespace Ninja\DeviceTracker;

use Illuminate\Foundation\Application;
use Ninja\DeviceTracker\Models\Device;

final readonly class DeviceManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }


    /**
     * @return bool
     */
    public function isUserDevice(): bool
    {
        return Device::isUserDevice();
    }

    public function deleteDevice($id): int
    {
        return Device::destroy($id);
    }

    public function addUserDevice(): bool
    {
        return Device::addUserDevice();
    }
}
