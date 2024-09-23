<?php

namespace Ninja\DeviceTracker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ninja\DeviceTracker\Models\Device;

final readonly class DeviceHijackedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Device $device, public Authenticatable $user)
    {
    }
}
