<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Dimension;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\AverageMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\CounterMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\GaugeMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\HistogramMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\PercentageMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\RateMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\SummaryMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Repository\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeRange;
use Throwable;

class DatabaseMetricAggregationRepository implements MetricAggregationRepository
{
    public const METRIC_AGGREGATION_TABLE = 'device_metrics';

    public function store(Metric $metric): void
    {
        try {
            DB::table(self::METRIC_AGGREGATION_TABLE)->updateOrInsert(
                [
                    'metric_fingerprint' => $metric->fingerprint(),
                ],
                [
                    'name' => $metric->name,
                    'type' => $metric->type->value,
                    'window' => $metric->aggregation->value,
                    'dimensions' => $metric->dimensions->toJson(),
                    'timestamp' => $metric->timestamp,
                    'value' => $metric->value->serialize(),
                    'computed' => $metric->value->value(),
                    'metadata' => json_encode($metric->value->metadata()),
                    'updated_at' => now(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Failed to store metric', [
                'metric' => $metric->array(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function query(): MetricQueryBuilder
    {
        return new MetricQueryBuilder(DB::table(self::METRIC_AGGREGATION_TABLE));
    }

    public function query(
        ?string $name = null,
        ?DimensionCollection $dimensions = null,
        ?Aggregation $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        return $this->buildQuery($name, $dimensions, $window, $from, $to)
            ->orderBy('timestamp')
            ->get()
            ->map(function ($row) {
                return new Metric(
                    name: $row->name,
                    type: MetricType::from($row->type),
                    value: $this->buildValue(
                        MetricType::from($row->type),
                        $row->value,
                        json_decode($row->metadata, true)
                    ),
                    timestamp: Carbon::parse($row->timestamp),
                    dimensions: DimensionCollection::from(json_decode($row->dimensions, true)),
                    aggregation: Aggregation::from($row->window),
                );
            });
    }

    public function latest(
        string $name,
        ?DimensionCollection $dimensions = null,
        ?Aggregation $window = null
    ): ?Metric {
        $result = $this->buildQuery($name, $dimensions, $window)
            ->latest('timestamp')
            ->first();

        return $result ? Metric::from($result) : null;
    }

    public function findByType(MetricType $type): Collection
    {
        return DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('type', $type->value)
            ->orderBy('timestamp')
            ->get()
            ->map(function (\stdClass $metric) {
                return Metric::from($metric);
            });
    }

    public function findAggregatedByType(MetricType $type, Aggregation $aggregation): Collection
    {
        return DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('type', $type->value)
            ->where('window', $aggregation->value)
            ->where('timestamp', '>=', now()->sub($aggregation->retention()))
            ->orderBy('timestamp')
            ->get()
            ->map(function (\stdClass $row) {
                return new Metric(
                    name: $row->name,
                    type: MetricType::from($row->type),
                    value: $this->buildValue(
                        MetricType::from($row->type),
                        $row->value,
                        json_decode($row->metadata, true)
                    ),
                    timestamp: Carbon::parse($row->timestamp),
                    dimensions: DimensionCollection::from(json_decode($row->dimensions, true)),
                    aggregation: Aggregation::from($row->window),
                );
            });
    }

    public function findByTimeRange(TimeRange $timeRange, array $names = []): Collection
    {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE)
            ->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);

        if (!empty($names)) {
            $query->whereIn('name', collect($names)->map->value->all());
        }

        return $query
            ->orderBy('timestamp')
            ->get()
            ->map(fn($metric) => Metric::from($metric));
    }

    public function findByCriteria(MetricCriteria $criteria): Collection
    {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE);

        if ($criteria->names) {
            $query->whereIn('name', collect($criteria->names)->map->value->all());
        }

        if ($criteria->types) {
            $query->whereIn('type', collect($criteria->types)->map->value->all());
        }

        if ($criteria->windows) {
            $query->whereIn('window', collect($criteria->windows)->map->value->all());
        }

        if ($criteria->timeRange) {
            $query->whereBetween('timestamp', [
                $criteria->timeRange->from,
                $criteria->timeRange->to
            ]);
        }

        if ($criteria->dimensions) {
            foreach ($criteria->dimensions as $dimension) {
                $query->where('dimensions', 'like', sprintf(
                    '%%"%s":"%s"%%',
                    $dimension->name,
                    $dimension->value
                ));
            }
        }

        return $query
            ->orderBy('timestamp')
            ->get()
            ->map(fn($metric) => Metric::from($metric));
    }

    public function stats(
        string $name,
        ?DimensionCollection $dimensions = null,
        ?Aggregation $window = null,
        ?TimeRange $timeRange = null
    ): array {
        $query = $this->buildQuery($name, $dimensions, $window);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        $stats = $query->selectRaw('
            COUNT(*) as count,
            MIN(CAST(value AS FLOAT)) as min_value,
            MAX(CAST(value AS FLOAT)) as max_value,
            AVG(CAST(value AS FLOAT)) as avg_value,
            MIN(timestamp) as first_seen,
            MAX(timestamp) as last_seen
        ')->first();

        return [
            'count' => $stats->count,
            'min_value' => $this->parseNumericValue($stats->min_value),
            'max_value' => $this->parseNumericValue($stats->max_value),
            'avg_value' => $this->parseNumericValue($stats->avg_value),
            'first_seen' => $stats->first_seen ? Carbon::parse($stats->first_seen) : null,
            'last_seen' => $stats->last_seen ? Carbon::parse($stats->last_seen) : null,
        ];
    }

    public function getDimensionValues(
        string $name,
        Dimension $dimension,
        ?TimeRange $timeRange = null
    ): Collection {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('name', $name)
            ->whereRaw("JSON_EXTRACT(dimensions, ?) IS NOT NULL", ["$.{$dimension->name}"]);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        return $query
            ->select(DB::raw(sprintf(
                "DISTINCT JSON_UNQUOTE(JSON_EXTRACT(dimensions, '$.%s')) as value",
                $dimension->name
            )))
            ->pluck('value');
    }

    public function aggregate(
        string $name,
        string $aggregation,
        ?TimeRange $timeRange = null,
        ?DimensionCollection $dimensions = null
    ): float {
        $query = $this->buildQuery($name, $dimensions);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        return match ($aggregation) {
            'count' => $query->count(),
            'sum' => $query->sum('computed') ?? 0,
            'avg' => $query->avg('computed') ?? 0,
            'min' => $query->min('computed') ?? 0,
            'max' => $query->max('computed') ?? 0,
            default => throw new InvalidArgumentException(sprintf('Invalid aggregation: %s', $aggregation))
        };
    }

    public function hasMetrics(Aggregation $window): bool
    {
        return DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('window', $window->value)
            ->where('timestamp', '>=', now()->sub($window->retention()))
            ->exists();
    }

    public function prune(Aggregation $window): int
    {
        $before = now()->sub($window->retention());

        return DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('window', $window->value)
            ->where('timestamp', '<', $before)
            ->delete();
    }

    private function buildQuery(
        ?string $name = null,
        ?DimensionCollection $dimensions = null,
        ?Aggregation $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Builder {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE);

        if ($name) {
            $query->where('name', $name);
        }

        if ($dimensions && !$dimensions->isEmpty()) {
            foreach ($dimensions as $dimension) {
                $query->where('dimensions', 'like', sprintf(
                    '%%"%s":"%s"%%',
                    $dimension->name,
                    $dimension->value
                ));
            }
        }

        if ($window) {
            $query->where('window', $window->value);
        }

        if ($from) {
            $query->where('timestamp', '>=', $from);
        }

        if ($to) {
            $query->where('timestamp', '<=', $to);
        }

        return $query;
    }

    private function buildValue(MetricType $type, string $stored, ?array $metadata = null): MetricValue
    {
        try {
            return HandlerFactory::compute($type, [
                ['value' => $stored, 'metadata' => $metadata]
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to reconstruct metric value', [
                'type' => $type->value,
                'stored' => $stored,
                'metadata' => $metadata,
                'error' => $e->getMessage()
            ]);

            return match ($type) {
                MetricType::Counter => CounterMetricValue::empty(),
                MetricType::Gauge => GaugeMetricValue::empty(),
                MetricType::Histogram => HistogramMetricValue::empty(),
                MetricType::Summary => SummaryMetricValue::empty(),
                MetricType::Average => AverageMetricValue::empty(),
                MetricType::Rate => RateMetricValue::empty(),
                MetricType::Percentage => PercentageMetricValue::empty(),
            };
        }
    }
}
