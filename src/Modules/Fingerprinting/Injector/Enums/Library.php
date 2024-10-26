<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Enums;

enum Library: string
{
    case FingerprintJS = 'fingerprintjs';
    case ClientJS = 'clientjs';
    case CreepJS = 'creepjs';
}
