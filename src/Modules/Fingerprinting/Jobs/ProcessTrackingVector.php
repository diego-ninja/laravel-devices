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

class ProcessTrackingVector implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly StorableId $device_uuid,
        private readonly string $vector
    ) {
        $this->onQueue('tracking');
    }

    public function handle(): void
    {
        $device = Device::byUuid($this->device_uuid);
        if (!$device) {
            return;
        }

        $device->fingerprint = $this->vector;
        $device->save();

        $this->process($device);
    }

    private function process(Device $device): void
    {
        $bits = str_split(decbin($this->vector));
        $points = Point::orderBy('index')->get();

        foreach ($points as $point) {
            if ($bits[$point->index] ?? '0' === '1') {
                dispatch(new ProcessTrackingPoint($device->uuid, $point->id));
            }
        }
    }
}
