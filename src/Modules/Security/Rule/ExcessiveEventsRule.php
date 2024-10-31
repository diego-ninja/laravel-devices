<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Device;

final class ExcessiveEventsRule extends AbstractSecurityRule
{
    public function evaluate(array $context): float
    {
        $device = Device::byUuid($context['device_uuid']);
        if (!$device) {
            return 0.0;
        }

        $eventCount = $device->events()
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $eventCount > $this->threshold ? 1.0 : 0.0;
    }
}
