<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Device;

final class MultipleSignupRule extends AbstractSecurityRule
{
    public function evaluate(array $context): float
    {
        $device = Device::where('fingerprint', $context['fingerprint'])->first();
        if (!$device) {
            return 0.0;
        }

        $signupCount = $device->users()
            ->where('created_at', '>=', now()->subDays(180))
            ->count();

        return $signupCount > $this->threshold ? 1.0 : 0.0;
    }
}
