<?php

namespace Ninja\DeviceTracker\Modules\Security\Context;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

final readonly class SecurityContext
{
    public function __construct(?Device $device = null, ?Session $session = null, ?Request $request = null)
    {
    }

    public static function current(): self
    {
        return new self(device(), session(), request());
    }
}