<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

enum MetricType: string
{
    case Counter = 'counter';
    case Gauge = 'gauge';
    case Histogram = 'histogram';
    case Summary = 'summary';
    case Average = 'average';
    case Rate = 'rate';

    public static function values(): array
    {
        return [
            self::Counter->value,
            self::Gauge->value,
            self::Histogram->value,
            self::Summary->value,
            self::Average->value,
            self::Rate->value,
        ];
    }
}
