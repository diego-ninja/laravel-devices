<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

final class Average extends AbstractMetricHandler
{
    public function compute(array $values): array
    {
        if (empty($values)) {
            return ['avg' => 0.0, 'sum' => 0.0, 'count' => 0];
        }

        $validValues = $this->filter($values);
        $sum = array_sum($validValues);
        $count = count($validValues);

        return [
            'avg' => $count > 0 ? $sum / $count : 0.0,
            'sum' => $sum,
            'count' => $count
        ];
    }

    public function merge(array $windows): array
    {
        $totalSum = 0;
        $totalCount = 0;

        foreach ($windows as $window) {
            if (is_array($window)) {
                $totalSum += $window['sum'] ?? 0;
                $totalCount += $window['count'] ?? 0;
            } else {
                $value = $this->extractValue($window);
                $totalSum += $value;
                $totalCount++;
            }
        }

        return [
            'avg' => $totalCount > 0 ? $totalSum / $totalCount : 0.0,
            'sum' => $totalSum,
            'count' => $totalCount
        ];
    }
}
