<?php

namespace Ninja\DeviceTracker\Modules\Observability\Collectors\Prometheus;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

final readonly class AggregatedMetricCollector extends AbstractPrometheusCollector
{
    public function __construct(
        private MetricAggregationRepository $repository
    ) {
    }

    public function register(): void
    {
        foreach (MetricType::cases() as $type) {
            $metrics = $this->repository->findByType($type);
            foreach ($metrics as $metric) {
                $this->metric(
                    name: $this->name($metric->name),
                    type: $type,
                    value: $metric->value,
                    labels: $this->labels($metric->dimensions)
                );
            }
        }
    }
}
