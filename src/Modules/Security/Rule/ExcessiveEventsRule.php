<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class ExcessiveEventsRule extends AbstractSecurityRule
{
    public function evaluate(SecurityContext $context): Factor
    {
        if (! $context->device) {
            return new Factor($this->factor, 0.0);
        }

        $eventCount = $context->device->events()
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $score = $eventCount > $this->threshold ? 1.0 : 0.0;

        return new Factor($this->factor, $score);
    }
}
