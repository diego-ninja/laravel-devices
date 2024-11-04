<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;

interface BehaviorAnalyzer
{
    public function analyze(Device $device): Risk;
}