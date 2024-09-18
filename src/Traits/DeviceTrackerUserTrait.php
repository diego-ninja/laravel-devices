<?php

namespace Ninja\DeviceTracker\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Models\Session;

trait DeviceTrackerUserTrait
{
    public function activeSessions($exceptSelf = false): HasMany
    {
        $query =  $this->sessions()->where('end_date', null)->where('block', Session::STATUS_DEFAULT)->where('login_code', null);

        if ($exceptSelf) {
            if (SessionFacade::has('dbsession.id')) {
                $query->where('id', '!=', SessionFacade::get('dbsession.id'));
            }
        }
        return $query;
    }

    public function sessions(): HasMany
    {
        return $this->hasMany('Ninja\DeviceTracker\Models\Session');
    }

    public function getFreshestSession(): Session
    {
        return $this->sessions()->orderBy('last_activity', 'desc')->first();
    }

    public function devices(): HasMany
    {
        return $this->hasMany('Ninja\DeviceTracker\Models\Device');
    }

    public function devicesUids(): array
    {
        $query = $this->devices()->pluck('uid');
        return $query->all();
    }
}
