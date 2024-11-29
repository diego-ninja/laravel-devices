<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Session;

final class HasManySessions extends HasMany
{
    public function first(): ?Session
    {
        /** @var Session|null $session */
        $session = $this
            ->with('device')
            ->orderBy('started_at')
            ->get()
            ->first();

        return $session;
    }

    public function last(): ?Session
    {
        /** @var Session|null $session */
        $session = $this
            ->with('device')
            ->orderByDesc('started_at')
            ->get()
            ->first();

        return $session;
    }

    public function current(): ?Session
    {
        /** @var Session|null $session */
        $session = $this
            ->with('device')
            ->where('uuid', session_uuid())
            ->get()
            ->first();

        return $session;
    }

    public function recent(): ?Session
    {
        /** @var Session|null $session */
        $session = $this
            ->with('device')
            ->where('status', SessionStatus::Active->value)
            ->orderByDesc('last_activity_at')
            ->get()
            ->first();

        return $session;
    }

    public function active(bool $exceptCurrent = false): Collection
    {
        $query = $this
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

        $this->each(function ($session) {
            /** @var Session $session */
            $session->end();
        });
    }
}
