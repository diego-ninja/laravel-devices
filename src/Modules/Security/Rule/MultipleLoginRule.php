<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

class MultipleLoginRule extends AbstractSecurityRule
{
    public function evaluate(SecurityContext $context): Factor
    {
        if (!$context->device) {
            return new Factor($this->factor, 0.0);
        }

        $uniqueLogins = $context->device->sessions()
            ->where('started_at', '>=', now()->subHours(4))
            ->distinct('user_id')
            ->count();

        $score = $uniqueLogins > $this->threshold ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }
}
