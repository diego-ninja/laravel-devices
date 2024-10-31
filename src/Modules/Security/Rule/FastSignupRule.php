<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Carbon\Carbon;
use Ninja\DeviceTracker\Models\Device;

final class FastSignupRule extends AbstractSecurityRule
{
    public function evaluate(array $context): float
    {
        $device = Device::byUuid($context['device_uuid']);
        if (!$device) {
            return 0.0;
        }

        $firstSeen = $device->created_at;
        $signupTime = Carbon::parse($context['signup_time']);

        return $signupTime->diffInSeconds($firstSeen) < 30 ? 1.0 : 0.0;
    }
}
