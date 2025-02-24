<?php

namespace Ninja\DeviceTracker\Modules\Security\Enums;

enum RiskLevelMode: string
{
    case Max = 'max';
    case Min = 'min';
    case Average = 'average';
    case WeightedAverage = 'weighted_average';
}
