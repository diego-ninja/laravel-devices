<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;

final readonly class Summary implements MetricHandler
{
    private array $quantiles;

    public function __construct(array $quantiles = [0.5, 0.9, 0.95, 0.99])
    {
        $this->quantiles = $quantiles;
    }

    public function compute(array $values): array
    {
        if (empty($values)) {
            return $this->empty();
        }

        sort($values);
        $result = [
            'count' => count($values),
            'sum' => array_sum($values),
            'min' => $values[0],
            'max' => end($values),
            'quantiles' => [],
        ];

        foreach ($this->quantiles as $q) {
            $result['quantiles'][$q] = $this->percentile($values, $q);
        }

        return $result;
    }

    public function merge(array $windows): array
    {
        if (empty($windows)) {
            return $this->empty();
        }

        $totalCount = 0;
        $totalSum = 0;
        $allValues = [];

        foreach ($windows as $window) {
            $totalCount += $window['count'];
            $totalSum += $window['sum'];
            // Para los cuantiles necesitamos recalcular con todos los valores
            // Esto es una simplificación, en producción podríamos usar t-digest o algoritmos más eficientes
            $allValues[] = $window['values'];
        }

        $allValues = array_merge(...$allValues);
        return $this->compute($allValues);
    }

    public function validate(float $value): bool
    {
        return true;
    }

    private function empty(): array
    {
        $result = [
            'count' => 0,
            'sum' => 0,
            'min' => 0,
            'max' => 0,
            'quantiles' => [],
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
