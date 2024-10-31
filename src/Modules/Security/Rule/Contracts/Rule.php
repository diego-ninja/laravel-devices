<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule\Contracts;

use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

interface Rule
{
    public function evaluate(array $context): Factor;
}