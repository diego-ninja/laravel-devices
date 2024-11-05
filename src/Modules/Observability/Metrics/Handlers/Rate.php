<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;

final readonly class Rate implements MetricHandler
{
    private int $interval;

    public function __construct(int $interval = 60)
    {
        $this->interval = $interval;
    }

    public function compute(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $timeRange = end($values)['timestamp'] - reset($values)['timestamp'];
        if ($timeRange <= 0) {
            return 0.0;
        }

        return (count($values) * $this->interval) / $timeRange;
    }

    public function merge(array $windows): float
    {
        if (empty($windows)) {
            return 0.0;
        }

        $totalCount = 0;
        $totalTime = 0;

        foreach ($windows as $window) {
            if (isset($window['count']) && isset($window['time'])) {
                $totalCount += $window['count'];
                $totalTime += $window['time'];
            }
        }

        return $totalTime > 0 ? ($totalCount * $this->interval) / $totalTime : 0.0;
    }

    public function validate(float $value): bool
    {
        return true;
    }
}
