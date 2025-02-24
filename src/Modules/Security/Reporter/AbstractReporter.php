<?php

namespace Ninja\DeviceTracker\Modules\Security\Reporter;

use Ninja\DeviceTracker\Modules\Security\Contracts\ReporterInterface;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;

abstract class AbstractReporter implements ReporterInterface
{
    protected RiskLevel $level;
    public function setLevel(RiskLevel $level): void
    {
        $this->level = $level;
    }
}
