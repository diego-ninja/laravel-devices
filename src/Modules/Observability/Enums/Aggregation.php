<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

use Carbon\Carbon;
use DateInterval;

enum Aggregation: string
{
    case Realtime = 'realtime';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';


    public function seconds(): int
    {
        return match ($this) {
            self::Realtime => 60,
            self::Hourly => 3600,
            self::Daily => 86400,
            self::Weekly => 604800,
            self::Monthly => 2592000,
            self::Yearly => 31536000,
        };
    }

    public function interval(): DateInterval
    {
        return match ($this) {
            self::Realtime => new DateInterval('PT1M'),
            self::Hourly => new DateInterval('PT1H'),
            self::Daily => new DateInterval('P1D'),
            self::Weekly => new DateInterval('P1W'),
            self::Monthly => new DateInterval('P1M'),
            self::Yearly => new DateInterval('P1Y'),
        };
    }

    public function retention(): DateInterval
    {
        $config_key = sprintf('devices.observability.aggregation.retention.%s', $this->value);
        if (config($config_key)) {
            return DateInterval::createFromDateString(config($config_key));
        }

        return match ($this) {
            self::Realtime => DateInterval::createFromDateString('1 hour'),
            self::Hourly => DateInterval::createFromDateString('1 day'),
            self::Daily => DateInterval::createFromDateString('1 week'),
            self::Weekly => DateInterval::createFromDateString('1 month'),
            self::Monthly => DateInterval::createFromDateString('1 year'),
            self::Yearly => DateInterval::createFromDateString('10 years'),
        };
    }

    public function timeslot(?Carbon $timestamp = null): int
    {
        $timestamp ??= now();

        $seconds = $this->seconds();
        return floor($timestamp->timestamp / $seconds) * $seconds;
    }

    public function previous(): ?Aggregation
    {
        return match ($this) {
            self::Yearly => self::Monthly,
            self::Monthly => self::Weekly,
            self::Weekly => self::Daily,
            self::Daily => self::Hourly,
            self::Hourly => self::Realtime,
            default => null,
        };
    }

    public function pattern(?Carbon $timestamp = null): string
    {
        if ($timestamp) {
            return sprintf(
                '*:*:%s:%d:*',
                $this->value,
                $this->timeslot($timestamp)
            );
        }

        return sprintf('*:*:%s:*:*', $this->value);
    }

    public function next(): ?Aggregation
    {
        return match ($this) {
            self::Realtime => self::Hourly,
            self::Hourly => self::Daily,
            self::Daily => self::Weekly,
            self::Weekly => self::Monthly,
            self::Monthly => self::Yearly,
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
            self::Yearly->value,
        ];
    }

    public static function wide(): array
    {
        return [
            self::Yearly,
            self::Monthly,
            self::Weekly,
            self::Daily,
            self::Hourly,
            self::Realtime,
        ];
    }

    public static function narrow(): array
    {
        return [
            self::Realtime,
            self::Hourly,
            self::Daily,
            self::Weekly,
            self::Monthly,
            self::Yearly,
        ];
    }
}
