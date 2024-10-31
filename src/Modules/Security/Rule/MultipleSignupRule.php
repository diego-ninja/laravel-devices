<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class MultipleSignupRule extends AbstractSecurityRule
{
    public function evaluate(array $context): Factor
    {
        $device = Device::where('fingerprint', $context['fingerprint'])->first();
        if (!$device) {
            return new Factor($this->factor, 0.0);
        }

        $signupCount = $device->users()
            ->where('created_at', '>=', now()->subDays(180))
            ->count();

        $score = $signupCount > $this->threshold ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }
}
