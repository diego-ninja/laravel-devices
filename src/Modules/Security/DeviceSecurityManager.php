<?php

namespace Ninja\DeviceTracker\Modules\Security;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;

final readonly class DeviceSecurityManager
{
    public function __construct(private RiskCalculator $threatCalculator)
    {
    }

    public function assess(SecurityContext $context): Risk
    {
        return $this->threatCalculator->risk($context);
    }

    public function handle(Device $device, Risk $risk): void
    {
    }
}
