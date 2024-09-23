<?php

namespace Ninja\DeviceTracker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ninja\DeviceTracker\Models\Session;

final readonly class SessionBlockedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Session $session, public Authenticatable $user)
    {
    }
}
