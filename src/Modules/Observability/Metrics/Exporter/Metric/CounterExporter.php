<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

final readonly class CounterExporter extends AbstractMetricExporter
{
    public function export(): array
    {
        return [
            'name' => sprintf('%s_total', $this->name),
            'type' => $this->type->value,
            'help' => $this->help(),
            'value' => $this->value(),
            'labels' => $this->labels()
        ];
    }
}