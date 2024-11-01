<?php

namespace Ninja\DeviceTracker\Modules\Security;

use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Rule\Collection\SecurityRuleCollection;

final readonly class RiskCalculator
{
    protected SecurityRuleCollection $rules;

    public function __construct()
    {
        $this->rules = SecurityRuleCollection::from(config('security.rules'));
    }

    public function risk(SecurityContext $context): Risk
    {
        return $this->rules->evaluate($context);
    }
}
