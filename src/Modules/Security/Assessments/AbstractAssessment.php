<?php

namespace Ninja\DeviceTracker\Modules\Security\Assessments;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\Contracts\RuleInterface;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityAssessmentInterface;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevelMode;

abstract readonly class AbstractAssessment implements SecurityAssessmentInterface
{
    /**
     * @param  Collection<RuleInterface>  $rules
     */
    public function __construct(private Collection $rules, private RiskLevelMode $riskLevelMode) {}

    public function evaluate(): Risk
    {
        $factors = collect();
        $this->rules->each(fn (RuleInterface $rule) => $factors->push(...$rule->evaluate()));

        return new Risk($factors, $this->riskLevelMode);
    }
}
