<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;

final readonly class Gauge implements MetricHandler
{
    public function compute(array $values): float
    {
        return end($values) ?: 0.0;
    }

    public function merge(array $windows): float
    {
        return end($windows) ?: 0.0;
    }

    public function validate(float $value): bool
    {
        return true;
    }
}
