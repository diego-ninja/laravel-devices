<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Validators;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;

final class MetricValueValidator
{
    private function __construct()
    {
    }

    public static function validate(
        MetricValue $value,
        ?float $min = null,
        ?float $max = null,
        bool $allowNegative = false
    ): bool {
        if (!$allowNegative && $value < 0) {
            return false;
        }

        if ($min !== null && $value < $min) {
            return false;
        }

        if ($max !== null && $value > $max) {
            return false;
        }

        return !is_infinite($value->value()) && !is_nan($value->value());
    }
}
