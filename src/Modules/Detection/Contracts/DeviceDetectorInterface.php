<?php

namespace Ninja\DeviceTracker\Modules\Detection\Contracts;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Device;

interface DeviceDetectorInterface
{
    public function detect(Request|string $request, ?Device $base = null): ?Device;
}
