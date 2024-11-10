<?php

namespace Ninja\DeviceTracker\Modules\Observability\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;

interface MetricAggregationRepository
{
    public function store(Metric $metric): void;
    public function query(?MetricName $name, ?DimensionCollection $dimensions = null, ?Aggregation $window = null, ?Carbon $from = null, ?Carbon $to = null): Collection;
    public function prune(Aggregation $window): int;
}
