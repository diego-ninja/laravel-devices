<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Illuminate\Console\Command;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Models\Device;

final class CleanupDevicesCommand extends Command
{
    protected $signature = 'devices:cleanup {--force : Force cleanup of hijacked devices}';

    protected $description = 'Clean up compromised and unused devices';

    public function handle(): void
    {
        if ($this->option('force') !== null) {
            $deletedHijacked = Device::where('status', DeviceStatus::Hijacked)->delete();
            $this->info(sprintf('Deleted %d hijacked devices.', $deletedHijacked));
        }

        $count = Device::orphans()->count();
        Device::orphans()->each(fn ($device) => $device->delete());

        $this->info(sprintf('Deleted %d orphaned devices.', $count));
    }
}
