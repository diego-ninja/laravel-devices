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
        if (! is_string($uuid)) {
            $this->error('Invalid UUID provided');

            return;
        }

        $device = Device::byUuid($uuid);

        if ($device === null) {
            $this->error(sprintf('Device with UUID %s not found', $uuid));

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
                ['Total Sessions', $device->sessions->count()],
                ['Associated Users', $device->users->count()],
            ]
        );
    }
}
