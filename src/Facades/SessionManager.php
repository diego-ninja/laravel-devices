<?php

namespace Ninja\DeviceTracker\Facades;

use Illuminate\Support\Facades\Facade;

final class SessionManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session';
    }
}
