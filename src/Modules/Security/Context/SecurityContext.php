<?php

namespace Ninja\DeviceTracker\Modules\Security\Context;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

class SecurityContext
{
    public function __construct(?Device $device, ?Session $session, ?Request $request = null)
    {
    }
}