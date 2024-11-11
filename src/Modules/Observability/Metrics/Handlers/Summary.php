<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\SummaryMetricValue;

final class Summary extends AbstractMetricHandler
{
    public function __construct(private readonly array $quantiles)
    {
    }

    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        $numValues = array_column($values, 'value');
        sort($numValues);

        $count = count($numValues);
        $sum = array_sum($numValues);
        $mean = $count > 0 ? $sum / $count : 0;

        return new SummaryMetricValue(
            value: $mean,
            quantiles: $this->quantiles,
            count: $count,
            sum: $sum
        );
    }
}
