<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

enum AggregationWindow: string
{
    case Realtime = 'realtime';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function seconds(): int
    {
        return match ($this) {
            self::Realtime => 60,
            self::Hourly => 3600,
            self::Daily => 86400,
            self::Weekly => 604800,
            self::Monthly => 2592000,
        };
    }

    public static function values(): array
    {
        return [
            self::Realtime->value,
            self::Hourly->value,
            self::Daily->value,
            self::Weekly->value,
            self::Monthly->value,
        ];
    }
}
