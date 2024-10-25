<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Enums;

enum PointType: string
{
    case Page = 'page';
    case Route = 'route';
    case Favicon = 'favicon';
}
