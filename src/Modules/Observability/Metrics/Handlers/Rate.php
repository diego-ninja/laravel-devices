<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\ValueObjects\RateWindow;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

final class Rate extends AbstractMetricHandler
{
    private readonly int $interval;

    public function __construct(int $interval = 3600)
    {
        parent::__construct(min: 0.0);
        $this->interval = $interval;
    }

    public function compute(array $values): array
    {
        if (count($values) < 2) {
            return [
                'rate' => 0.0,
                'count' => count($values),
                'interval' => $this->interval
            ];
        }

        $window = RateWindow::fromValues($values, $this->interval);
        if ($window->empty() || $window->duration() <= 0) {
            return [
                'rate' => (float)count($values),
                'count' => count($values),
                'interval' => $this->interval
            ];
        }

        $validValues = $this->filter($values);
        $rate = (count($validValues) * $this->interval) / $window->duration();

        return [
            'rate' => $rate,
            'count' => count($validValues),
            'interval' => $this->interval,
            'window_start' => $window->start,
            'window_end' => $window->end
        ];
    }

    public function merge(array $windows): array
    {
        $totalCount = 0;
        $totalDuration = 0;

        foreach ($windows as $window) {
            if (isset($window['count'], $window['window_start'], $window['window_end'])) {
                $totalCount += $window['count'];
                $totalDuration += ($window['window_end'] - $window['window_start']);
            }
        }

        return [
            'rate' => $totalDuration > 0 ? ($totalCount * $this->interval) / $totalDuration : 0.0,
            'count' => $totalCount,
            'interval' => $this->interval
        ];
    }
}
