<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Ninja\DeviceTracker\Models\Device;

final class BelongsToManyDevices extends BelongsToMany
{
    public function current(): ?Device
    {
        return $this->where('uuid', device_uuid())->first();
    }

    public function uuids(): array
    {
        return $this->pluck('uuid')->toArray();
    }
}
