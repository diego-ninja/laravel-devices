<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Ninja\DeviceTracker\Models\Device;

interface PatternRepository
{
    public function history(Device $device, int $hours = 24): array;
}