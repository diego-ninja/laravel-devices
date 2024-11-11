<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter;

use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric\Factory;
use Ninja\DeviceTracker\Modules\Observability\Repository\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;

final readonly class AggregatedMetricExporter extends AbstractMetricExporter
{
    public function __construct(
        private MetricAggregationRepository $repository
    ) {
        parent::__construct();
    }
    protected function collect(): array
    {
        $aggregation = $this->aggregation();
        if ($aggregation === null) {
            return [];
        }

        $result = [];

        foreach (MetricType::cases() as $type) {
            $metrics = $this->repository->findAggregatedByType($type, $aggregation);
            $exportedMetrics = $metrics->map(function (Metric $metric) {
                return Factory::create($metric)->export();
            })->toArray();

            $result = array_merge($result, $exportedMetrics);
        }

        return $result;
    }

    private function aggregation(): ?Aggregation
    {
        $windows = Aggregation::wide();
        foreach ($windows as $window) {
            if ($this->repository->hasMetrics($window)) {
                return $window;
            }
        }

        return null;
    }
}
