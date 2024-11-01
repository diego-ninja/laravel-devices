<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Enums\EventType;
use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class FastSignupRule extends AbstractSecurityRule
{
    public function evaluate(SecurityContext $context): Factor
    {
        if (!$context->device) {
            return new Factor($this->factor, 0.0);
        }

        $firstSeen = $context->device->created_at;
        $signupTime = $context->device
            ->events()
            ->type(EventType::Signup)
            ->first()->occurred_at;

        $score = $signupTime->diffInSeconds($firstSeen) < $this->threshold ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }
}
