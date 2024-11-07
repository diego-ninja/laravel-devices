<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Traits;

trait HandlesMetricValues
{
    protected function extractValue(mixed $value): float
    {
        if (is_array($value)) {
            return isset($value['value'])
                ? (float) $value['value']
                : (float) ($value[0] ?? 0.0);
        }
        return (float) $value;
    }

    protected function extractTimestamp(mixed $value): ?int
    {
        if (is_array($value) && isset($value['timestamp'])) {
            return (int) $value['timestamp'];
        }
        return null;
    }

    protected function normalize(array $values): array
    {
        return array_map(fn($v) => $this->extractValue($v), $values);
    }

    protected function sortByTimestamp(array $values): array
    {
        usort($values, function ($a, $b) {
            $tsA = $this->extractTimestamp($a) ?? 0;
            $tsB = $this->extractTimestamp($b) ?? 0;
            return $tsB <=> $tsA;
        });

        return $values;
    }
}
