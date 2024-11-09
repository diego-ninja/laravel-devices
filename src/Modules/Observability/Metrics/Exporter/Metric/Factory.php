<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts\Exportable;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;

/**
 * @internal
 */
final readonly class Factory
{
    public static function create(Metric $metric): Exportable
    {
        return match ($metric->type) {
            MetricType::Counter => CounterExporter::from($metric),
            MetricType::Gauge => GaugeExporter::from($metric),
            MetricType::Average => AverageExporter::from($metric),
            MetricType::Histogram => HistogramExporter::from($metric),
            MetricType::Rate => RateExporter::from($metric),
            MetricType::Summary => SummaryExporter::from($metric),
            default => throw new InvalidArgumentException(sprintf('Unsupported metric type: %s', $metric->type->value)),
        };
    }
}
