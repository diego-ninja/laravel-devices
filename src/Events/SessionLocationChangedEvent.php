<?php

namespace Ninja\DeviceTracker\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;

final class SessionLocationChangedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Session $session,
        public readonly Location $oldLocation,
        public readonly Carbon $lastActivityAt,
        public readonly Location $currentLocation,
        public readonly Carbon $currentActivityAt,
    ) {}
}
