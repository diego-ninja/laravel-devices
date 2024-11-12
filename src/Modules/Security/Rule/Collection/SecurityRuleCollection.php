<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule\Collection;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Rule\AbstractSecurityRule;
use Ninja\DeviceTracker\Modules\Security\Rule\Contracts\Rule;

final class SecurityRuleCollection extends Collection
{
    public function enabled(): self
    {
        return $this->filter(fn ($rule) => $rule->enabled());
    }

    public function evaluate(SecurityContext $context): Risk
    {
        $total = 0;
        $weight = 0;

        $risk = Risk::default();

        $this->enabled()->each(function (AbstractSecurityRule $rule) use (&$total, &$weight, $context, $risk) {
            $factor = $rule->evaluate($context);
            $total += $factor->score * $rule->weight;
            $weight += $rule->weight;

            $risk->factor($factor);
        });

        $risk->score =  $weight > 0 ? ($total / $weight) * 100 : 0;

        return $risk;
    }

    public static function from(string|array|Collection $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new SecurityRuleCollection(
            collect($data)->map(fn (array|Rule $rule, string $key) => is_array($rule) ? $this->rule($rule, $key) : $rule)
        );
    }

    private function rule(array $data, string $factor): Rule
    {
        $data['factor'] = $factor;

        $class = $data['class'];
        return $class::from($data);
    }
}
