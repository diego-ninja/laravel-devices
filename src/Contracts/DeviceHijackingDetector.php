<?php

namespace Ninja\DeviceTracker\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\DeviceTracker\Models\Device;

interface DeviceHijackingDetector
{
    public function detect(Device $device, ?Authenticatable $user): bool;
}
