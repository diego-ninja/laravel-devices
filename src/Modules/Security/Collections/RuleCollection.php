<?php

namespace Ninja\DeviceTracker\Modules\Security\Collections;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\Contracts\RuleInterface;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;

/**
 * @template-extends Collection<RuleInterface>
 */
final class RuleCollection extends Collection
{
    public function enabled(): self
    {
        return $this->filter(fn ($rule) => $rule->enabled());
    }

    public function evaluate(): Risk
    {
        $factors = $this->enabled()->reduce(fn (Collection $carry, RuleInterface $rule) => $carry->push($rule->evaluate()), collect());

        return new Risk($factors);
    }
}
