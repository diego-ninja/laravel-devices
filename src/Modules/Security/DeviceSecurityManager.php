<?php

namespace Ninja\DeviceTracker\Modules\Security;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;

final readonly class DeviceSecurityManager
{
    public function __construct(private ThreatCalculator $threatCalculator)
    {
    }

    public function assess(Device $device): Risk
    {
        $session = Session::current();

        $context  = [
            'session' => $session,
            'device' => $device
        ];

        return $this->threatCalculator->score($context);
    }

    public function handle(Device $device, Risk $risk): void
    {
    }
}
