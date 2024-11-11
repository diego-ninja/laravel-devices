<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository\Contracts;

use Closure;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeRange;

interface MetricQueryBuilder
{
    public function withDimension(string $name, string $value): self;
    public function withDimensions(DimensionCollection $dimensions): self;
    public function withType(MetricType $type): self;
    public function withTypes(array $types): self;
    public function withWindow(Aggregation $window): self;
    public function withTimeRange(TimeRange $timeRange): self;
    public function withName(string $name): self;
    public function orderBy(string $column, string $direction = 'asc'): self;
    public function orderByValue(string $direction = 'asc'): self;
    public function orderByTimestamp(string $direction = 'asc'): self;
    public function limit(int $limit): self;
    public function havingValue(string $operator, float $value): self;
    public function groupByDimension(string $dimension): self;
    public function groupByDimensions(array $dimensions): self;
    public function groupByTimeWindow(string $interval = '1 hour'): self;

    public function whereInSubquery(string $column, Closure $callback): self;

    public function joinMetrics(
        string $metricName,
        ?string $alias = null,
        ?Closure $callback = null,
        string $joinType = 'inner'
    ): self;
    public function wherePercentile(float $percentile, string $direction = '>='): self;
    public function withCorrelatedMetrics(string $metricName, float $threshold = 0.7): self;
    public function withChangeRate(): self;
    public function aggregate(string $function, array $columns): self;
    public function stats(): array;
    /**
     * @return Collection<Metric>
     */
    public function get(): Collection;
    public function first(): ?Metric;
    public function count(): int;
    public function sum(): float;
    public function avg(): float;
    public function min(): float;
    public function max(): float;
}
