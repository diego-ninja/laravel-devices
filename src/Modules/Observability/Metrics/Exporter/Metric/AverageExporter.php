<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

final readonly class AverageExporter extends AbstractMetricExporter
{
    public function export(): array
    {
        return [
            [
                'name' => sprintf('%s_avg', $this->name),
                'type' => MetricType::Gauge->value,
                'help' => sprintf("%s (average)", $this->help()),
                'value' => $this->value("avg"),
                'labels' => $this->labels()
            ],
            [
                'name' => sprintf('%s_sum', $this->name),
                'type' => MetricType::Counter->value,
                'help' => sprintf("%s (sum)", $this->help()),
                'value' => $this->value("sum"),
                'labels' => $this->labels()
            ],
            [
                'name' => sprintf('%s_count', $this->name),
                'type' => MetricType::Counter->value,
                'help' => sprintf("%s (count)", $this->help()),
                'value' => $this->value("count"),
                'labels' => $this->labels()
            ]
        ];
    }
}
