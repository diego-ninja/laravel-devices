<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class SessionHijackedRule extends AbstractSecurityRule
{
    public function evaluate(SecurityContext $context): Factor
    {
        if (! $context->session) {
            return new Factor($this->factor, 0.0);
        }

        $uniqueDevices = $context->session->device()
            ->where('created_at', '>=', now()->subHours(4))
            ->distinct('fingerprint')
            ->count();

        $score = $uniqueDevices > $this->threshold ? 1.0 : 0.0;

        return new Factor($this->factor, $score);
    }
}
