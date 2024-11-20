<?php

namespace Ninja\DeviceTracker\Traits;

use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\HasManySessions;
use Ninja\DeviceTracker\Models\Relations\BelongsToManyDevices;
use Ninja\DeviceTracker\Models\Session;

trait HasDevices
{
    public function sessions(): HasManySessions
    {
        $instance = $this->newRelatedInstance(Session::class);

        return new HasManySessions(
            query: $instance->newQuery(),
            parent: $this,
            foreignKey: 'user_id',
            localKey: 'id'
        );
    }

    public function devices(): BelongsToManyDevices
    {
        $table = sprintf('%s_devices', str(\config('devices.authenticatable_table'))->singular());
        $field = sprintf('%s_id', str(\config('devices.authenticatable_table'))->singular());

        $instance = $this->newRelatedInstance(Device::class);

        return new BelongsToManyDevices(
            query: $instance->newQuery(),
            parent: $this,
            table: $table,
            foreignPivotKey: $field,
            relatedPivotKey: 'device_uuid',
            parentKey: 'id',
            relatedKey: 'uuid',
        );
    }

    public function device(): ?Device
    {
        return $this->devices()->current();
    }

    public function session(): Session
    {
        return $this->sessions()->current();
    }
    public function hasDevice(Device|string $device): bool
    {
        $deviceId = $device instanceof Device ? $device->uuid : DeviceIdFactory::from($device);
        return in_array($deviceId, $this->devices()->uuids());
    }

    public function addDevice(Device $device): bool
    {
        if ($this->hasDevice($device->uuid)) {
            return true;
        }

        $this->devices()->attach($device->uuid);
        $this->save();

        return true;
    }

    public function inactive(): bool
    {
        if ($this->sessions()->count() > 0) {
            $lastActivity = $this->sessions()->recent()->last_activity_at;
            return $lastActivity && abs(strtotime($lastActivity) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200);
        }

        return true;
    }
}
