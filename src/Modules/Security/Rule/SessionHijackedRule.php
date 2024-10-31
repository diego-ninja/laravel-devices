<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Session;

final class SessionHijackedRule extends AbstractSecurityRule
{
    public function evaluate(array $context): float
    {
        $session = Session::where('uuid', $context['session_id'])->first();
        if (!$session) {
            return 0.0;
        }

        $uniqueDevices = $session->device()
            ->where('created_at', '>=', now()->subHours(4))
            ->distinct('fingerprint')
            ->count();

        return $uniqueDevices > 1 ? 1.0 : 0.0;
    }
}
