<?php

namespace Ninja\DeviceTracker\Modules\Observability\Collectors\Prometheus;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;

abstract readonly class AbstractPrometheusCollector implements Collector
{
    abstract public function register(): void;

    protected function metric(
        string $name,
        MetricType $type,
        mixed $value,
        array $labels
    ): void {
        $namespace = config('devices.metrics.prometheus.namespace', 'devices');

        match ($type) {
            MetricType::Counter => $this->counter($namespace, $name, $value, $labels),
            MetricType::Gauge,
            MetricType::Rate,
            MetricType::Average => $this->gauge($namespace, $name, $value, $labels),
            MetricType::Histogram => $this->histogram($namespace, $name, $value, $labels),
            MetricType::Summary => $this->summary($namespace, $name, $value, $labels),
        };
    }

    protected function counter(string $namespace, string $name, float $value, array $labels): void
    {
        Prometheus::getOrRegisterCounter(
            namespace: $namespace,
            name: $name,
            help: "Device metric: {$name}",
            labelNames: array_keys($labels)
        )->set($value, array_values($labels));
    }

    protected function gauge(string $namespace, string $name, float $value, array $labels): void
    {
        Prometheus::getOrRegisterGauge(
            namespace: $namespace,
            name: $name,
            help: "Device metric: {$name}",
            labelNames: array_keys($labels)
        )->set($value, array_values($labels));
    }

    protected function histogram(string $namespace, string $name, array $value, array $labels): void
    {
        $histogram = Prometheus::getOrRegisterHistogram(
            namespace: $namespace,
            name: $name,
            help: "Device metric: {$name}",
            labelNames: array_keys($labels),
            buckets: array_keys($value['buckets'] ?? [0.1, 1, 10, 100])
        );

        foreach ($value['buckets'] as $bucket => $count) {
            for ($i = 0; $i < $count; $i++) {
                $histogram->observe($bucket, array_values($labels));
            }
        }
    }

    protected function summary(string $namespace, string $name, array $value, array $labels): void
    {
        foreach ($value['quantiles'] as $quantile => $v) {
            Prometheus::getOrRegisterGauge(
                namespace: $namespace,
                name: "{$name}_quantile",
                help: "Device metric: {$name} quantile {$quantile}",
                labelNames: [...array_keys($labels), 'quantile']
            )->set($v, [...array_values($labels), $quantile]);
        }

        Prometheus::getOrRegisterGauge(
            namespace: $namespace,
            name: "{$name}_count",
            help: "Device metric: {$name} count",
            labelNames: array_keys($labels)
        )->set($value['count'], array_values($labels));

        Prometheus::getOrRegisterGauge(
            namespace: $namespace,
            name: "{$name}_sum",
            help: "Device metric: {$name} sum",
            labelNames: array_keys($labels)
        )->set($value['sum'], array_values($labels));
    }

    protected function name(string $name): string
    {
        return str_replace(['.', '-', ' '], '_', strtolower($name));
    }

    protected function labels(array $dimensions): array
    {
        $labels = [];
        foreach ($dimensions as $dimension) {
            $labels[$dimension['name']] = $dimension['value'];
        }
        return $labels;
    }
}
