<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\HistogramMetricValue;

final class Histogram extends AbstractMetricHandler
{
    public function __construct(private readonly array $buckets)
    {
    }

    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        $count = count($values);
        $sum = array_sum(array_column($values, 'value'));
        $mean = $count > 0 ? $sum / $count : 0;

        return new HistogramMetricValue(
            value: $mean,
            buckets: $this->buckets,
            count: $count,
            sum: $sum
        );
    }
}
