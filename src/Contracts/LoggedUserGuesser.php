<?php

namespace Ninja\DeviceTracker\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface LoggedUserGuesser
{
    public static function guess(): ?Authenticatable;
}
