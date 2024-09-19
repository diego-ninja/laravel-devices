<?php

namespace Ninja\DeviceTracker\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

trait HasDevices
{
    public function activeSessions($exceptSelf = false): HasMany
    {
        $query =  $this->sessions()
            ->where('end_date', null)
            ->where('block', Session::STATUS_DEFAULT)
            ->where('login_code', null);

        if ($exceptSelf) {
            if (SessionFacade::has(Session::DEVICE_SESSION_ID)) {
                $query->where('id', '!=', SessionFacade::get(Session::DEVICE_SESSION_ID));
            }
        }
        return $query;
    }

    public function recentSession(): Session
    {
        return $this->sessions()->orderBy('last_activity', 'desc')->first();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function currentDevice(): Device
    {
        return $this->devices()->where('uid', SessionFacade::get('d_i'))->first();
    }
    public function isUserDevice(): bool
    {
        if (SessionFacade::has('d_i')) {
            if (in_array(SessionFacade::get('d_i'), $this->devicesUids())) {
                return true;
            }
        }
        return false;
    }

    public function devicesUids(): array
    {
        $query = $this->devices()->pluck('uid');
        return $query->all();
    }
}
