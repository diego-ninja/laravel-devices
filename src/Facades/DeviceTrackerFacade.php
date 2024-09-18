<?php

namespace Ninja\DeviceTracker\Facades;

use Illuminate\Support\Facades\Facade;

final  class DeviceTrackerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'deviceTracker';
    }
}
