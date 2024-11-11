<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository\Contracts;

use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;

interface MetricAggregationRepository
{
    public function store(Metric $metric): void;
    public function query(): MetricQueryBuilder;
    public function prune(Aggregation $window): int;
    public function hasMetrics(Aggregation $aggregation): bool;
}
