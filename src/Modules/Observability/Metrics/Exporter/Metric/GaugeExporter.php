<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

use Ninja\DeviceTracker\Modules\Observability\Dto\Dimension;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts\Exportable;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;

final readonly class GaugeExporter extends AbstractMetricExporter implements Exportable
{
    public function export(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'help' => Registry::get($this->name)->description(),
            'value' => $this->value(),
            'labels' => $this->labels()
        ];
    }
}