<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Carbon\Carbon;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class FastSignupRule extends AbstractSecurityRule
{
    public function evaluate(array $context): Factor
    {
        $device = Device::byUuid($context['device_uuid']);
        if (!$device) {
            return new Factor($this->factor, 0.0);
        }

        $firstSeen = $device->created_at;
        $signupTime = Carbon::parse($context['signup_time']);

        $score = $signupTime->diffInSeconds($firstSeen) < 30 ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }
}
