<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Ninja\DeviceTracker\Models\Device;

/**
 * @extends BelongsToMany<Device, User>
 */
final class BelongsToManyDevices extends BelongsToMany
{
    public function current(): ?Device
    {
        /** @var Device|null $device */
        $device = $this->where('uuid', device_uuid())->first();

        return $device;
    }

    /**
     * @return array<string>
     */
    public function uuids(): array
    {
        return $this->pluck('uuid')->toArray();
    }
}
