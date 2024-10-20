<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Session;

final class HasManySessions extends HasMany
{
    public function first(): Session
    {
        return $this->orderBy('started_at')->first();
    }

    public function last(): Session
    {
        return $this->orderByDesc('started_at')->first();
    }

    public function current(): Session
    {
        return $this->where('id', SessionFacade::get(Session::DEVICE_SESSION_ID))->first();
    }

    public function recent(): Session
    {
        return $this
            ->where('status', SessionStatus::Active)
            ->orderBy('last_activity_at', 'desc')->first();
    }

    public function active(bool $exceptCurrent = false): HasManySessions
    {
        $query =  $this
            ->where('finished_at', null)
            ->where('status', SessionStatus::Active);

        if ($exceptCurrent) {
            if (SessionFacade::has(Session::DEVICE_SESSION_ID)) {
                $query->where('id', '!=', SessionFacade::get(Session::DEVICE_SESSION_ID));
            }
        }

        return $query;
    }

    public function finished(): HasManySessions
    {
        return $this
            ->whereNotNull('finished_at')
            ->where('status', SessionStatus::Finished)
            ->orderBy('finished_at', 'desc');
    }

    public function signout(bool $logoutCurrentSession = false): void
    {
        if ($logoutCurrentSession) {
            $this->current()->end();
        }

        $this->each(fn (Session $session) => $session->end());
    }
}
