<?php

namespace Ninja\DeviceTracker\Modules\Security\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;

class DeviceRiskUpdatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Device $device, Risk $old, Risk $new)
    {
    }
}
