<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

final class Gauge extends AbstractMetricHandler
{
    public function compute(array $values): array
    {
        if (empty($values)) {
            return ['value' => 0.0, 'timestamp' => time()];
        }

        $sorted = $this->sortByTimestamp($values);
        $latest = reset($sorted);

        return [
            'value' => $this->extractValue($latest),
            'timestamp' => $this->extractTimestamp($latest) ?? time()
        ];
    }

    public function merge(array $windows): array
    {
        if (empty($windows)) {
            return ['value' => 0.0, 'timestamp' => time()];
        }

        $latest = null;
        $latestTimestamp = 0;

        foreach ($windows as $window) {
            $timestamp = $this->extractTimestamp($window);
            if ($timestamp && $timestamp > $latestTimestamp) {
                $latest = $window;
                $latestTimestamp = $timestamp;
            }
        }

        return [
            'value' => $latest ? $this->extractValue($latest) : 0.0,
            'timestamp' => $latestTimestamp ?: time()
        ];
    }
}
