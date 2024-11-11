<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

class Percentage extends AbstractMetricHandler
{
    public function compute(array $values): array
    {
        $validValues = $this->filter($values);
        if (empty($validValues)) {
            return [
                'value' => 0,
                'total' => 0,
                'percentage' => 0,
                'count' => 0
            ];
        }

        $latest = end($validValues);

        return [
            'value' => $latest['value'] ?? 0,
            'total' => $latest['total'] ?? 0,
            'percentage' => $latest['total'] > 0
                ? ($latest['value'] / $latest['total']) * 100
                : 0,
            'count' => count($validValues)
        ];
    }

    public function merge(array $windows): array
    {
        $latest = collect($windows)
            ->sortByDesc('timestamp')
            ->first();

        return [
            'value' => $latest['value'] ?? 0,
            'total' => $latest['total'] ?? 0,
            'percentage' => $latest['total'] > 0
                ? ($latest['value'] / $latest['total']) * 100
                : 0,
            'count' => count($windows)
        ];
    }
}
