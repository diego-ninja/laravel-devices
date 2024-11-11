<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\GaugeMetricValue;

final class Gauge extends AbstractMetricHandler
{
    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        usort($values, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        $latest = reset($values);

        return new GaugeMetricValue(
            $latest['value'],
            $latest['timestamp']
        );
    }
}
