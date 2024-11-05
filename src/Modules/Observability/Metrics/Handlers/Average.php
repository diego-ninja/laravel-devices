<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;

final readonly class Average implements MetricHandler
{
    public function compute(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        return array_sum($values) / count($values);
    }

    public function merge(array $windows): float
    {
        if (empty($windows)) {
            return 0.0;
        }

        $totalSum = 0;
        $totalCount = 0;

        foreach ($windows as $window) {
            if (isset($window['sum']) && isset($window['count'])) {
                $totalSum += $window['sum'];
                $totalCount += $window['count'];
            }
        }

        return $totalCount > 0 ? $totalSum / $totalCount : 0.0;
    }

    public function validate(float $value): bool
    {
        return true;
    }
}
