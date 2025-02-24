<?php

namespace Ninja\DeviceTracker\Modules\Security\Enums;

enum RiskLevel: int
{
    case None = 0;
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;
}
