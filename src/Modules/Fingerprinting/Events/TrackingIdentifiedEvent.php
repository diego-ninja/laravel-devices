<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackingIdentifiedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $device_uuid,
        public readonly string $vector
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('tracking');
    }
}
