<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule\Collection;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\Rule\AbstractSecurityRule;
use Ninja\DeviceTracker\Modules\Security\Rule\Contracts\Rule;

final class SecurityRuleCollection extends Collection implements Rule
{
    public function enabled(): self
    {
        return $this->filter(fn ($rule) => $rule->enabled());
    }

    public function evaluate(array $context): float
    {
        $total = 0;
        $weight = 0;

        $this->enabled()->each(function (AbstractSecurityRule $rule) use (&$total, &$weight, $context) {
            $score = $rule->evaluate($context);
            $total += $score * $rule->weight;
            $weight += $rule->weight;
        });

        return $weight > 0 ? ($total / $weight) * 100 : 0;
    }

    public static function from(string|array|Collection $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new SecurityRuleCollection(
            collect($data)->map(fn (array|Rule $rule) => is_array($rule) ? $this->rule($rule) : $rule)
        );
    }

    private function rule(array $data): Rule
    {
        $class = $data['class'];
        return $class::from($data);
    }
}
