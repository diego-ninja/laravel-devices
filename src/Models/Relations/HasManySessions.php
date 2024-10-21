<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Session;

final class HasManySessions extends HasMany
{
    public function first(): ?Session
    {
        return $this->orderBy('started_at')->get()->first();
    }

    public function last(): ?Session
    {
        return $this->orderByDesc('started_at')->get()->first();
    }

    public function current(): ?Session
    {
        return $this->where('uuid', SessionFacade::get(Session::DEVICE_SESSION_ID))->get()->first();
    }

    public function recent(): ?Session
    {
        return $this
            ->where('status', SessionStatus::Active)
            ->orderBy('last_activity_at', 'desc')
            ->get()
            ->first();
    }

    public function active(bool $exceptCurrent = false): Collection
    {
        $query =  $this
            ->where('finished_at', null)
            ->where('status', SessionStatus::Active);

        if ($exceptCurrent) {
            if (SessionFacade::has(Session::DEVICE_SESSION_ID)) {
                $query->where('id', '!=', SessionFacade::get(Session::DEVICE_SESSION_ID));
            }
        }

        return $query->get();
    }

    public function finished(): Collection
    {
        return $this
            ->whereNotNull('finished_at')
            ->where('status', SessionStatus::Finished)
            ->orderBy('finished_at', 'desc')
            ->get();
    }

    public function signout(bool $logoutCurrentSession = false): void
    {
        if ($logoutCurrentSession) {
            $this->current()->end();
        }

        $this->each(fn (Session $session) => $session->end());
    }
}
