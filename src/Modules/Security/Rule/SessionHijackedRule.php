<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class SessionHijackedRule extends AbstractSecurityRule
{
    public function evaluate(array $context): Factor
    {
        $session = Session::where('uuid', $context['session_id'])->first();
        if (!$session) {
            return new Factor($this->factor, 0.0);
        }

        $uniqueDevices = $session->device()
            ->where('created_at', '>=', now()->subHours(4))
            ->distinct('fingerprint')
            ->count();

        $score = $uniqueDevices > 1 ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }
}
