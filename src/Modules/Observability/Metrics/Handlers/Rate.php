<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\RateMetricValue;

final class Rate extends AbstractMetricHandler
{
    public function __construct(private readonly int $interval)
    {
    }

    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        if (empty($values)) {
            return new RateMetricValue(0, $this->interval);
        }

        $timestamps = array_column($values, 'timestamp');
        $timespan = max($timestamps) - min($timestamps);

        if ($timespan <= 0) {
            return new RateMetricValue(count($values), $this->interval);
        }

        $rate = (count($values) * $this->interval) / $timespan;

        return new RateMetricValue($rate, $this->interval, count($values));
    }
}
