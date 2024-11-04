<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

enum MetricType: string
{
    case Counter = 'counter';
    case Gauge = 'gauge';
    case Histogram = 'histogram';
}
