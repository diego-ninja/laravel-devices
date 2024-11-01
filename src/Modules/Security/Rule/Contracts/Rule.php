<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule\Contracts;

use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

interface Rule
{
    public function evaluate(SecurityContext $context): Factor;
}
