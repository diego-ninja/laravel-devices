<?php

namespace Ninja\DeviceTracker\Contracts;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Device;

interface DeviceDetector
{
    public function detect(Request $request): Device;
}