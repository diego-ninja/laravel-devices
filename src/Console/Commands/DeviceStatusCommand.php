<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Illuminate\Console\Command;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

final class DeviceStatusCommand extends Command
{
    protected $signature = 'devices:status';

    protected $description = 'Show device tracker status information';

    public function handle(): void
    {
        $this->info('Device Tracker Status:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Devices', Device::count()],
                ['Verified Devices', Device::where('status', DeviceStatus::Verified)::count()],
                ['Unverified Devices', Device::where('status', DeviceStatus::Unverified)::count()],
                ['Hijacked Devices', Device::where('status', DeviceStatus::Hijacked)::count()],
                ['Active Sessions', Session::where('status', SessionStatus::Active)::count()],
                ['Locked Sessions', Session::where('status', SessionStatus::Locked)::count()],
                ['Blocked Sessions', Session::where('status', SessionStatus::Blocked)::count()],
                ['Finished Sessions', Session::where('status', SessionStatus::Finished)::count()],
            ]
        );
    }
}
