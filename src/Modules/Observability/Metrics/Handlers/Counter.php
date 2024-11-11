<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\CounterMetricValue;

final class Counter extends AbstractMetricHandler
{
    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        return new CounterMetricValue(
            array_sum(array_column($values, 'value'))
        );
    }
}
