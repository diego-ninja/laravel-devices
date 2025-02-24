<?php

namespace Ninja\DeviceTracker\Modules\Security\Assessments;

use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevelMode;
use Ninja\DeviceTracker\Modules\Security\Rules\SessionsWithSameIpAndUserAgentRule;

readonly class SessionsWithSameIpAndUserAgentAssessment extends AbstractAssessment
{
    private string $name;
    /**
     * @param  array<int, int>  $countLimits
     * @param  array<int, int>|null  $weights
     */
    public function __construct(int $seconds, array $countLimits, ?array $weights = null)
    {
        $rules = collect();

        foreach ($countLimits as $key => $limit) {
            $rules->push(
                new SessionsWithSameIpAndUserAgentRule(
                    name: sprintf('sessions-with-same-ip-and-user-agent-%s-%s', $seconds, $limit),
                    weight: $weights[$key] ?? $key + 1,
                    thresholds: ['seconds' => $seconds, 'count' => $limit],
                    enabled: true,
                    description: sprintf(
                        'Checks that there are not multiple sessions for the same combination of ip/user agent for more than %s user',
                        $limit,
                    )
                ),
            );
        }
        parent::__construct(
            $rules,
            RiskLevelMode::WeightedAverage,
        );

        $this->name = sprintf(
            'Sessions with same IP and UserAgent assessment: %ss, %s count limits',
            $seconds,
            implode('|', $countLimits),

        );
    }

    public function name(): string
    {
        return $this->name;

    }

    public function description(): string
    {
        return 'Checks that there are not multiple sessions for the same combination of ip/user agent and different users';
    }
}
