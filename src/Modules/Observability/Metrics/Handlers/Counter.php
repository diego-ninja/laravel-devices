<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

final class Counter extends AbstractMetricHandler
{
    public function __construct()
    {
        parent::__construct(min: 0.0);
    }

    public function compute(array $values): array
    {
        $validValues = $this->filter($values);

        return [
            'value' => array_sum($validValues),
            'count' => count($validValues)
        ];
    }

    public function merge(array $windows): array
    {
        $total = 0;
        $count = 0;

        foreach ($windows as $window) {
            if (is_array($window)) {
                $total += $window['value'] ?? 0;
                $count += $window['count'] ?? 0;
            } else {
                $total += $this->extractValue($window);
                $count++;
            }
        }

        return [
            'value' => $total,
            'count' => $count
        ];
    }
}
