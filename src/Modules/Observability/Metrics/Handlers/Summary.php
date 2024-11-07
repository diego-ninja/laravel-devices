<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

final class Summary extends AbstractMetricHandler
{
    private readonly array $quantiles;

    public function __construct(array $quantiles = [0.5, 0.9, 0.95, 0.99])
    {
        parent::__construct();
        $this->quantiles = $quantiles;
    }

    public function compute(array $values): array
    {
        if (empty($values)) {
            return $this->empty();
        }

        $validValues = $this->filter($values);
        sort($validValues);

        $result = [
            'count' => count($validValues),
            'sum' => array_sum($validValues),
            'min' => $validValues[0],
            'max' => end($validValues),
            'quantiles' => [],
            'values' => $validValues
        ];

        foreach ($this->quantiles as $q) {
            $result['quantiles'][$q] = $this->percentile($validValues, $q);
        }

        return $result;
    }

    public function merge(array $windows): array
    {
        if (empty($windows)) {
            return $this->empty();
        }

        $allValues = [];
        foreach ($windows as $window) {
            if (isset($window['values'])) {
                $allValues = array_merge($allValues, $window['values']);
            } else {
                $allValues[] = $this->extractValue($window);
            }
        }

        return $this->compute($allValues);
    }

    private function empty(): array
    {
        $result = [
            'count' => 0,
            'sum' => 0,
            'min' => 0,
            'max' => 0,
            'quantiles' => [],
            'values' => []
        ];

        foreach ($this->quantiles as $q) {
            $result['quantiles'][$q] = 0;
        }

        return $result;
    }

    private function percentile(array $values, float $q): float
    {
        $count = count($values);
        $rank = $q * ($count - 1);
        $low_index = floor($rank);
        $high_index = ceil($rank);
        $fraction = $rank - $low_index;

        if ($high_index >= $count) {
            return end($values);
        }

        if ($low_index == $high_index) {
            return $values[$low_index];
        }

        return $values[$low_index] * (1 - $fraction) + $values[$high_index] * $fraction;
    }
}
