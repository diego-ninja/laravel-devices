<?php

namespace Ninja\DeviceTracker\Modules\Detection\Contracts;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Device;

interface DeviceDetector
{
    public function detect(Request|string $request): ?Device;
}
