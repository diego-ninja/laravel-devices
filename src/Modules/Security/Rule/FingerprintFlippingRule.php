<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Session;

final class FingerprintFlippingRule extends AbstractSecurityRule
{
    public function evaluate(array $context): float
    {
        $session = Session::current();
        if (!$session) {
            return 0.0;
        }

        $changes = Session::where('user_id', $session->user_id)
            ->where('created_at', '>=', now()->subHour())
            ->distinct('device_uuid')
            ->count();

        return $changes > $this->threshold ? 1.0 : 0.0;
    }
}
