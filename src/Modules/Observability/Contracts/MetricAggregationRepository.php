<?php

namespace Ninja\DeviceTracker\Modules\Observability\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

interface MetricAggregationRepository
{
    public function store(MetricName $name, MetricType $type, float $value, array $dimensions, Carbon $timestamp, AggregationWindow $window): void;
    public function query(MetricName $name, array $dimensions = [], ?AggregationWindow $window = null, ?Carbon $from = null, ?Carbon $to = null): Collection;
    public function prune(AggregationWindow $window, Carbon $before): int;

}