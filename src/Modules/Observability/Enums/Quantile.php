<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

enum Quantile: string
{
    case P50 = 'p50';
    case P75 = 'p75';
    case P90 = 'p90';
    case P95 = 'p95';
    case P99 = 'p99';

    public static function scale(): array
    {
        return [0.5, 0.75, 0.9, 0.95, 0.99];
    }
    public function value(): float
    {
        return match ($this) {
            self::P50 => 0.5,
            self::P75 => 0.75,
            self::P90 => 0.9,
            self::P95 => 0.95,
            self::P99 => 0.99,
        };
    }
}
