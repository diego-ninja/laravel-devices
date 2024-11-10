<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Dto\Dimension;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeRange;
use Throwable;

class DatabaseMetricAggregationRepository implements MetricAggregationRepository
{
    public const METRIC_AGGREGATION_TABLE = 'device_metrics';

    public function store(Metric $metric): void
    {
        try {
            $storedValue = $this->formatValueForStorage($metric->type, $metric->value);

            DB::table(self::METRIC_AGGREGATION_TABLE)->updateOrInsert(
                [
                    'metric_fingerprint' => $metric->fingerprint(),
                ],
                [
                    'value' => $storedValue,
                    'created_at' => now(),
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

    public function query(
        ?MetricName $name = null,
        ?DimensionCollection $dimensions = null,
        ?Aggregation $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        return $this->buildQuery($name, $dimensions, $window, $from, $to)
            ->orderBy('timestamp')
            ->get()
            ->map(function ($metric) {
                $metric->value = $this->parseStoredValue(
                    MetricType::from($metric->type),
                    $metric->value
                );
                return $metric;
            });
    }

    public function latest(
        MetricName $name,
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
            ->map(function (\stdClass $metric) {
                return Metric::from($metric);
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
        MetricName $name,
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
        MetricName $name,
        Dimension $dimension,
        ?TimeRange $timeRange = null
    ): Collection {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('name', $name->value)
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

    public function timeseries(
        MetricName $name,
        string $interval,
        ?TimeRange $timeRange = null,
        ?DimensionCollection $dimensions = null
    ): Collection {
        $query = $this->buildQuery($name, $dimensions);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        return $query->select([
            DB::raw(sprintf(
                "DATE_FORMAT(timestamp, '%s') as time",
                $this->getTimeFormat($interval)
            )),
            'type',
            'value',
            'window'
        ])
            ->orderBy('time')
            ->get()
            ->map(function ($row) {
                $row->value = $this->parseStoredValue(
                    MetricType::from($row->type),
                    $row->value
                );
                return $row;
            });
    }

    public function aggregate(
        MetricName $name,
        string $aggregation,
        ?TimeRange $timeRange = null,
        ?DimensionCollection $dimensions = null
    ): float {
        $query = $this->buildQuery($name, $dimensions);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        $result = match ($aggregation) {
            'count' => $query->count(),
            'sum' => $query->sum(DB::raw('CAST(value AS FLOAT)')),
            'avg' => $query->avg(DB::raw('CAST(value AS FLOAT)')),
            'min' => $query->min(DB::raw('CAST(value AS FLOAT)')),
            'max' => $query->max(DB::raw('CAST(value AS FLOAT)')),
            default => throw new InvalidArgumentException("Unknown aggregation: {$aggregation}")
        };

        return $this->parseNumericValue($result);
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

    private function getTimeFormat(string $interval): string
    {
        return match ($interval) {
            'minute' => '%Y-%m-%d %H:%i:00',
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d 00:00:00',
            'week' => '%Y-%u-1',
            'month' => '%Y-%m-01',
            default => '%Y-%m-%d %H:%i:%s'
        };
    }

    private function buildQuery(
        ?MetricName $name = null,
        ?DimensionCollection $dimensions = null,
        ?Aggregation $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Builder {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE);

        if ($name) {
            $query->where('name', $name->value);
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

    private function formatValueForStorage(MetricType $type, mixed $value): string
    {
        try {
            return match ($type) {
                MetricType::Counter,
                MetricType::Gauge => (string) ($value['value'] ?? $value),
                MetricType::Rate => (string) ($value['rate'] ?? $value),
                MetricType::Average => (string) ($value['avg'] ?? $value),
                MetricType::Summary,
                MetricType::Histogram => is_string($value) ? $value : json_encode($value)
            };
        } catch (Throwable $e) {
            Log::error('Failed to format metric value', [
                'type' => $type->value,
                'value' => json_encode($value),
                'error' => $e->getMessage()
            ]);
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid value format for metric type %s: %s',
                    $type->value,
                    json_encode($value)
                ),
                0,
                $e
            );
        }
    }

    private function parseStoredValue(MetricType $type, string $value): mixed
    {
        return match ($type) {
            MetricType::Summary,
            MetricType::Histogram => json_decode($value, true),
            MetricType::Counter,
            MetricType::Gauge,
            MetricType::Rate,
            MetricType::Average => $this->parseNumericValue($value)
        };
    }
    private function parseNumericValue(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
