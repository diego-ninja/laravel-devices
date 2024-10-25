<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Point;

class ProcessTrackingPoint implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly StorableId $device_uuid,
        private readonly int $pointId
    ) {
        $this->onQueue('tracking-points');
    }

    public function handle(): void
    {
        $device = Device::byUuid($this->device_uuid);
        $point = Point::find($this->pointId);

        if (!$device || !$point) {
            return;
        }

        $tracking = $device->tracking()->firstOrCreate();
        $point->track($tracking, ['processed_at' => now()]);
    }
}
