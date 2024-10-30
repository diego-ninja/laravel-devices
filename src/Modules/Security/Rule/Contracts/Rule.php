<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule\Contracts;

interface Rule
{
    public function evaluate(array $context): float;
}