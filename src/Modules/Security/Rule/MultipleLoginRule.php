<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

class MultipleLoginRule extends AbstractSecurityRule
{
    public function evaluate(array $context): Factor
    {
        $device = Device::byUuid($context['device_uuid']);
        if (!$device) {
            return new Factor($this->factor, 0.0);
        }

        $uniqueLogins = $device->sessions()
            ->where('created_at', '>=', now()->subHours(4))
            ->distinct('user_id')
            ->count();

        $score = $uniqueLogins > $this->threshold ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }
}
