<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\MetricCriteria;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeRange;

class DatabaseMetricAggregationRepository implements MetricAggregationRepository
{
    public const METRIC_AGGREGATION_TABLE = 'device_metrics';

    public function store(
        MetricName $name,
        MetricType $type,
        float|string $value,
        array $dimensions,
        Carbon $timestamp,
        AggregationWindow $window
    ): void {
        DB::table(self::METRIC_AGGREGATION_TABLE)->insert([
            'name' => $name->value,
            'type' => $type->value,
            'value' => $value,
            'dimensions' => json_encode($dimensions),
            'timestamp' => $timestamp,
            'window' => $window->value,
            'created_at' => now()
        ]);
    }

    public function query(
        ?MetricName $name,
        ?array $dimensions = [],
        ?AggregationWindow $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        return $this
            ->buildQuery($name, $dimensions, $window, $from, $to)
            ->orderBy('timestamp')
            ->get();
    }

    public function latest(
        MetricName $name,
        array $dimensions = [],
        ?AggregationWindow $window = null
    ): ?float {
        $result = $this->buildQuery($name, $dimensions, $window)
            ->latest('timestamp')
            ->first();

        return $result ? (float) $result->value : null;
    }

    public function findByTimeRange(TimeRange $timeRange, array $names = []): Collection
    {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE)
            ->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);

        if (!empty($names)) {
            $query->whereIn('name', collect($names)->map->value->all());
        }

        return $query->orderBy('timestamp')->get();
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
            foreach ($criteria->dimensions as $key => $value) {
                $query->where('dimensions', 'like', "%\"{$key}\":\"{$value}\"%");
            }
        }

        return $query->orderBy('timestamp')->get();
    }

    public function stats(
        MetricName $name,
        array $dimensions = [],
        ?AggregationWindow $window = null,
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
            'min_value' => $stats->min_value,
            'max_value' => $stats->max_value,
            'avg_value' => $stats->avg_value,
            'first_seen' => $stats->first_seen ? Carbon::parse($stats->first_seen) : null,
            'last_seen' => $stats->last_seen ? Carbon::parse($stats->last_seen) : null,
        ];
    }

    public function getDimensionValues(
        MetricName $name,
        string $dimension,
        ?TimeRange $timeRange = null
    ): Collection {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('name', $name->value)
            ->whereRaw("JSON_EXTRACT(dimensions, ?) IS NOT NULL", ["$.{$dimension}"]);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        return $query
            ->select(
                DB::raw(sprintf("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(dimensions, '$.%s')) as value", $dimension))
            )
            ->pluck('value');
    }

    public function timeseries(
        MetricName $name,
        string $interval,
        ?TimeRange $timeRange = null,
        array $dimensions = []
    ): Collection {
        $query = $this->buildQuery($name, $dimensions);

        if ($timeRange) {
            $query->whereBetween('timestamp', [$timeRange->from, $timeRange->to]);
        }

        return $query->select([
            DB::raw(sprintf("DATE_FORMAT(timestamp, '%s') as time", $this->getTimeFormat($interval))),
            DB::raw('CAST(AVG(CAST(value AS FLOAT)) AS FLOAT) as value'),
            'window'
        ])
            ->groupBy('time', 'window')
            ->orderBy('time')
            ->get();
    }

    public function prune(AggregationWindow $window, Carbon $before): int
    {
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
        array $dimensions = [],
        ?AggregationWindow $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Builder {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE);

        if ($name) {
            $query->where('name', $name->value);
        }

        if (!empty($dimensions)) {
            foreach ($dimensions as $key => $value) {
                $query->where('dimensions', 'like', "%\"{$key}\":\"{$value}\"%");
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
}
