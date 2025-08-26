<?php

namespace Ninja\DeviceTracker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Ninja\DeviceTracker\SessionManager
 */
final class SessionManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session_manager';
    }
}
