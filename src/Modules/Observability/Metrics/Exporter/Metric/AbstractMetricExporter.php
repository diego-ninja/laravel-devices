<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

use Ninja\DeviceTracker\Modules\Observability\Dto\Dimension;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts\Exportable;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;

abstract readonly class AbstractMetricExporter implements Exportable
{
    public function __construct(
        public MetricName $name,
        public MetricType $type,
        public float|array $value,
        public DimensionCollection $dimensions,
    ) {
    }

    public static function from(Metric|array|string $metric): self
    {
        if (is_array($metric) || is_string($metric)) {
            $metric = Metric::from($metric);
        }

        return new static(
            name: $metric->name,
            type: $metric->type,
            value: $metric->value,
            dimensions: $metric->dimensions,
        );
    }

    public function help(): string
    {
        return Registry::get($this->name)->description();
    }

    public function labels(): array
    {
        return $this->dimensions->map(function (Dimension $dimension) {
            return $dimension->array();
        })->toArray();
    }

    public function value(?string $key = null): string
    {
        return is_array($this->value) ? $this->value[$key ?? "value"] : (string) $this->value;
    }

    abstract public function export(): array;
}
