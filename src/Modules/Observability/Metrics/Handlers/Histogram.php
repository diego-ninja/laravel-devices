<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;

final readonly class Histogram implements MetricHandler
{
    private array $buckets;

    public function __construct(array $buckets)
    {
        sort($buckets);
        $this->buckets = $buckets;
    }

    public function compute(array $values): array
    {
        sort($values);
        $total = count($values);
        $result = [
            'count' => $total,
            'sum' => array_sum($values),
            'min' => $total > 0 ? $values[0] : 0,
            'max' => $total > 0 ? end($values) : 0,
            'avg' => $total > 0 ? array_sum($values) / $total : 0,
            'buckets' => $this->buckets($values),
        ];

        $result['p50'] = $this->percentile($values, 0.5);
        $result['p90'] = $this->percentile($values, 0.9);
        $result['p95'] = $this->percentile($values, 0.95);
        $result['p99'] = $this->percentile($values, 0.99);

        return $result;
    }

    public function merge(array $windows): array
    {
        $mergedBuckets = [];
        $totalCount = 0;
        $totalSum = 0;
        $allMins = [];
        $allMaxs = [];

        foreach ($windows as $window) {
            $totalCount += $window['count'];
            $totalSum += $window['sum'];
            $allMins[] = $window['min'];
            $allMaxs[] = $window['max'];

            foreach ($window['buckets'] as $bucket => $count) {
                $mergedBuckets[$bucket] = ($mergedBuckets[$bucket] ?? 0) + $count;
            }
        }

        return [
            'count' => $totalCount,
            'sum' => $totalSum,
            'min' => $totalCount > 0 ? min($allMins) : 0,
            'max' => $totalCount > 0 ? max($allMaxs) : 0,
            'avg' => $totalCount > 0 ? $totalSum / $totalCount : 0,
            'buckets' => $mergedBuckets,
        ];
    }

    public function validate(float $value): bool
    {
        return true;
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
