<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Illuminate\Console\Command;
use Ninja\DeviceTracker\Models\Device;

final class DeviceInspectCommand extends Command
{
    protected $signature = 'devices:inspect {uuid : Device UUID to inspect}';

    protected $description = 'Inspect detailed information about a specific device';

    public function handle(): void
    {
        $uuid = $this->argument('uuid');
        $device = Device::byUuid($uuid);

        if (! $device) {
            $this->error("Device not found with UUID: {$uuid}");

            return;
        }

        $this->info('Device Information:');
        $this->table(
            ['Property', 'Value'],
            [
                ['UUID', $device->uuid],
                ['Status', $device->status->value],
                ['Browser', $device->browser],
                ['Platform', $device->platform],
                ['Device Type', $device->device_type],
                ['IP', $device->ip],
                ['Created', $device->created_at],
                ['Last Updated', $device->updated_at],
                ['Active Sessions', $device->sessions()->active()->count()],
                ['Total Sessions', $device->sessions()->count()],
                ['Associated Users', $device->users()->count()],
            ]
        );
    }
}
