<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class MultipleSignupRule extends AbstractSecurityRule
{
    public function evaluate(SecurityContext $context): Factor
    {
        if (! $context->device) {
            return new Factor($this->factor, 0.0);
        }

        $signupCount = $context->device->users()
            ->where('created_at', '>=', now()->subDays(180))
            ->count();

        $score = $signupCount > $this->threshold ? 1.0 : 0.0;

        return new Factor($this->factor, $score);
    }
}
