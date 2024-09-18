<?php

namespace Ninja\DeviceTracker;

use Illuminate\Support\Facades\Facade;

final  class DeviceTrackerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'deviceTracker';
    }
}
