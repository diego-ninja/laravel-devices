<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Device;

class MultipleLoginRule extends AbstractSecurityRule
{
    public function evaluate(array $context): float
    {
        $device = Device::byUuid($context['device_uuid']);
        if (!$device) {
            return 0.0;
        }

        $uniqueLogins = $device->sessions()
            ->where('created_at', '>=', now()->subHours(4))
            ->distinct('user_id')
            ->count();

        return $uniqueLogins > $this->threshold ? 1.0 : 0.0;
    }
}
