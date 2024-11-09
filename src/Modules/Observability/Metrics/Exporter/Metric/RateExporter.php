<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;

final readonly class RateExporter extends AbstractMetricExporter
{
    public function export(): array
    {
        $definition = Registry::get($this->name);

        return [
            'name' => sprintf('%s_per_%s', $this->name->value, $definition->unit()),
            'type' => MetricType::Gauge->value,
            'help' => $this->help(),
            'value' => $this->value(),
            'labels' => $this->labels()
        ];
    }
}