<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Validators;

final class MetricValueValidator
{
    private function __construct()
    {
    }

    public static function validate(
        float $value,
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

        return !is_infinite($value) && !is_nan($value);
    }
}
