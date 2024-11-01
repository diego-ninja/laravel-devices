<?php

namespace Ninja\DeviceTracker\Modules\Security\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DeviceSecurityManager;
use Ninja\DeviceTracker\Modules\Security\Events\DeviceRiskUpdatedEvent;

final readonly class CalculateDeviceRiskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private DeviceSecurityManager $manager, private SecurityContext $context)
    {
        $this->onQueue('security');
    }

    public function handle(): void
    {
        $device = $this->context->device;

        $old = $device->risk;
        $new = $this->manager->assess($this->context);

        $device->risk = $new;
        $device->risk_assessed_at = now();
        $device->save();

        if ($new->changed($old)) {
            event(new DeviceRiskUpdatedEvent($device, $old, $new));
        }
    }
}
