<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Session;

final class HasManySessions extends HasMany
{
    public function first(): ?Session
    {
        return $this
            ->with('device')
            ->orderBy('started_at')
            ->get()
            ->first();
    }

    public function last(): ?Session
    {
        return $this
            ->with('device')
            ->orderByDesc('started_at')
            ->get()
            ->first();
    }

    public function current(): ?Session
    {
        return $this
            ->with('device')
            ->where('uuid', session_uuid())
            ->get()
            ->first();
    }

    public function recent(): ?Session
    {
        return $this
            ->with('device')
            ->where('status', SessionStatus::Active->value)
            ->orderByDesc('last_activity_at')
            ->get()
            ->first();
    }

    public function active(bool $exceptCurrent = false): Collection
    {
        $query =  $this
            ->with('device')
            ->where('finished_at', null)
            ->where('status', SessionStatus::Active);

        if ($exceptCurrent) {
            if (session_uuid()) {
                $query->where('id', '!=', session_uuid());
            }
        }

        return $query->get();
    }

    public function finished(): Collection
    {
        return $this
            ->with('device')
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
