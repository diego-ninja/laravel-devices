<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

final class Histogram extends AbstractMetricHandler
{
    private readonly array $buckets;

    public function __construct(array $buckets)
    {
        parent::__construct(min: 0.0);
        sort($buckets);
        $this->buckets = $buckets;
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
            'avg' => array_sum($validValues) / count($validValues),
            'buckets' => $this->buckets($validValues),
            'values' => $validValues
        ];

        $result['p50'] = $this->percentile($validValues, 0.5);
        $result['p90'] = $this->percentile($validValues, 0.9);
        $result['p95'] = $this->percentile($validValues, 0.95);
        $result['p99'] = $this->percentile($validValues, 0.99);

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
            }
        }

        return $this->compute($allValues);
    }

    private function empty(): array
    {
        return [
            'count' => 0,
            'sum' => 0,
            'min' => 0,
            'max' => 0,
            'avg' => 0,
            'buckets' => array_fill_keys($this->buckets, 0),
            'p50' => 0,
            'p90' => 0,
            'p95' => 0,
            'p99' => 0,
            'values' => []
        ];
    }

    private function buckets(array $values): array
    {
        $buckets = array_fill_keys($this->buckets, 0);
        foreach ($values as $value) {
            foreach ($this->buckets as $bucket) {
                if ($value <= $bucket) {
                    $buckets[$bucket]++;
                }
            }
        }
        return $buckets;
    }

    private function percentile(array $values, float $p): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $count = count($values);
        $rank = $p * ($count - 1);
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
