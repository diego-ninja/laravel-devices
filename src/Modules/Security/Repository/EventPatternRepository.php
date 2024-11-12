<?php

namespace Ninja\DeviceTracker\Modules\Security\Repository;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\Contracts\PatternRepository;
use Ninja\DeviceTracker\Modules\Tracking\Models\Event;

final readonly class EventPatternRepository implements PatternRepository
{
    public function history(Device $device, int $hours = 24): array
    {
        return Event::where('device_uuid', $device->uuid)
            ->where('occurred_at', '>=', now()->subHours($hours))
            ->orderBy('occurred_at', 'desc')
            ->get()
            ->map(fn (Event $event) => $event->type)
            ->toArray();
    }
}
