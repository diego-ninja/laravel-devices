<?php

namespace Ninja\DeviceTracker\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ramsey\Uuid\Uuid;

trait HasDevices
{
    public function activeSessions($exceptSelf = false): HasMany
    {
        $query =  $this->sessions()
            ->where('finished_at', null)
            ->where('status', SessionStatus::Active);

        if ($exceptSelf) {
            if (SessionFacade::has(Session::DEVICE_SESSION_ID)) {
                $query->where('id', '!=', SessionFacade::get(Session::DEVICE_SESSION_ID));
            }
        }

        return $query;
    }

    public function signout(bool $logoutCurrentSession = false): void
    {
        if ($logoutCurrentSession) {
            $this->currentSession()->end();
        }

        $this->sessions->each(fn (Session $session) => $session->end());
    }

    public function recentSession(): Session
    {
        return $this->sessions()->orderBy('last_activity_at', 'desc')->first();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'user_id');
    }

    public function currentDevice(): Device
    {
        return $this->devices->where(
            key: 'uuid',
            operator: '=',
            value: Device::getDeviceUuid()
        )->first();
    }

    public function currentSession(): Session
    {
        return $this->sessions->where(
            key: 'uuid',
            operator: '=',
            value: SessionFacade::get(Session::DEVICE_SESSION_ID)
        )->first();
    }
    public function hasDevice(Device|string $device): bool
    {
        $deviceId = $device instanceof Device ? $device->uuid : Uuid::fromString($device);
        return in_array($deviceId, $this->devicesUids());
    }

    public function addDevice(Device $device): bool
    {
        if ($this->hasDevice($device->uuid)) {
            return true;
        }

        $device->user_id = $this->id;
        $device->save();

        return true;
    }

    public function isInactive(): bool
    {
        if ($this->sessions()->count() > 0) {
            $lastActivity = $this->recentSession()->last_activity_at;
            return $lastActivity && abs(strtotime($lastActivity) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200);
        }

        return true;
    }

    public function devicesUids(): array
    {
        $query = $this->devices()->pluck('uuid');
        return $query->all();
    }
}
