<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;

final readonly class Counter implements MetricHandler
{
    public function compute(array $values): float
    {
        return array_sum($values);
    }

    public function merge(array $windows): float
    {
        return array_sum($windows);
    }

    public function validate(float $value): bool
    {
        return $value >= 0;
    }
}
