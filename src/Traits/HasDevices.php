<?php

namespace Ninja\DeviceTracker\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session as SessionFacade;
use Jenssegers\Agent\Agent;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait HasDevices
{
    public function activeSessions($exceptSelf = false): HasMany
    {
        $query =  $this->sessions()
            ->where('finished_at', null)
            ->where('block', false)
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
        return $this->devices()->where(
            column: 'uuid',
            value: SessionFacade::get(Config::get("devices.device_id_cookie_name"))
        )->first();
    }

    public function currentSession(): Session
    {
        return $this->sessions()->where(
            column: 'uuid',
            value: SessionFacade::get(Session::DEVICE_SESSION_ID)
        )->first();
    }
    public function hasDevice(UuidInterface $uuid): bool
    {
        return in_array($uuid, $this->devicesUids());
    }

    public function addDevice(?string $userAgent = null): bool
    {
        $cookieName = Config::get('devices.device_id_cookie_name');
        if (Cookie::has($cookieName)) {
            if ($this->hasDevice(Uuid::fromString(Cookie::get($cookieName)))) {
                return true;
            }

            $agent = new Agent(
                headers: request()->headers->all(),
                userAgent: $userAgent ?? request()->userAgent()
            );

            Device::create([
                'user_id' => $this->id,
                'uuid' => Uuid::fromString(Cookie::get($cookieName)),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'mobile' => $agent->isMobile(),
                'device' => $agent->device(),
                'device_type' => $agent->deviceType(),
                'robot' => $agent->isRobot(),
                'source' => $agent->getUserAgent(),
                'ip' => request()->ip(),
            ]);

            return true;
        }

        return false;
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
