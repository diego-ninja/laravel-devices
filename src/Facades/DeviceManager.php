<?php

namespace Ninja\DeviceTracker\Facades;

use Illuminate\Support\Facades\Facade;

final class DeviceManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'device';
    }
}
