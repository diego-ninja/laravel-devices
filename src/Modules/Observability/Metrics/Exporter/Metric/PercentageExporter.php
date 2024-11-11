<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts\Exportable;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;

final readonly class PercentageExporter extends AbstractMetricExporter implements Exportable
{
    public function export(): array
    {
        return [
            'name' => $this->name->value,
            'type' => $this->type->value,
            'help' => Registry::get($this->name)->description(),
            'value' => $this->value(),
            'labels' => $this->labels()
        ];
    }
}