<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

use Carbon\Carbon;

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

    public function timeslot(?Carbon $timestamp = null): int
    {
        $timestamp ??= now();

        $seconds = $this->seconds();
        return floor($timestamp->timestamp / $seconds) * $seconds;
    }

    public function previous(): ?AggregationWindow
    {
        return match ($this) {
            self::Monthly => self::Weekly,
            self::Weekly => self::Daily,
            self::Daily => self::Hourly,
            self::Hourly => self::Realtime,
            default => null,
        };
    }

    public function next(): ?AggregationWindow
    {
        return match ($this) {
            self::Realtime => self::Hourly,
            self::Hourly => self::Daily,
            self::Daily => self::Weekly,
            self::Weekly => self::Monthly,
            default => null,
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
