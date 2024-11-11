<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter;

use Ninja\DeviceTracker\Modules\Observability\Dto\Dimension;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts\MetricExporter;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Formatter\PrometheusTextFormatter;

abstract readonly class AbstractMetricExporter implements MetricExporter
{
    protected PrometheusTextFormatter $formatter;
    public function __construct()
    {
        $this->formatter = new PrometheusTextFormatter();
    }

    abstract protected function collect(): array;

    public function export(): string
    {
        return $this->formatter->format($this->collect());
    }

    protected function name(string $name): string
    {
        return str_replace(['.', '-', ' '], '_', strtolower($name));
    }

    protected function labels(DimensionCollection $dimensions): array
    {
        return $dimensions->each(function (Dimension $dimension) {
            return $dimension->asLabel();
        })->toArray();
    }
}