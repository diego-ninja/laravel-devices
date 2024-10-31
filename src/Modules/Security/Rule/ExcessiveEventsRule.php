<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class ExcessiveEventsRule extends AbstractSecurityRule
{
    public function evaluate(array $context): Factor
    {
        $device = Device::byUuid($context['device_uuid']);
        if (!$device) {
            return new Factor($this->factor, 0.0);
        }

        $eventCount = $device->events()
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $score =  $eventCount > $this->threshold ? 1.0 : 0.0;

        return new Factor($this->factor, $score);
    }
}
