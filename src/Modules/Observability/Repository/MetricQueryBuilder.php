<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeRange;

class MetricQueryBuilder implements Contracts\MetricQueryBuilder
{
    private const METRIC_TABLE = 'device_metrics';

    private array $dimensions = [];
    private array $types = [];
    private ?Aggregation $window = null;
    private ?TimeRange $timeRange = null;
    private ?string $name = null;
    private array $orderBy = [];
    private ?int $limit = null;
    private array $having = [];
    private array $joins = [];

    public function __construct(private readonly Builder $query)
    {
    }

    public function withDimension(string $name, string $value): self
    {
        $this->dimensions[] = ['name' => $name, 'value' => $value];
        return $this;
    }

    public function withDimensions(DimensionCollection $dimensions): self
    {
        foreach ($dimensions as $dimension) {
            $this->withDimension($dimension->name, $dimension->value);
        }
        return $this;
    }

    public function withType(MetricType $type): self
    {
        $this->types[] = $type;
        return $this;
    }

    public function withTypes(array $types): self
    {
        foreach ($types as $type) {
            $this->withType($type);
        }
        return $this;
    }

    public function withWindow(Aggregation $window): self
    {
        $this->window = $window;
        return $this;
    }

    public function withTimeRange(TimeRange $timeRange): self
    {
        $this->timeRange = $timeRange;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = [$column, $direction];
        return $this;
    }

    public function orderByValue(string $direction = 'asc'): self
    {
        return $this->orderBy('computed', $direction);
    }

    public function orderByTimestamp(string $direction = 'asc'): self
    {
        return $this->orderBy('timestamp', $direction);
    }

    public function groupByDimension(string $dimension): self
    {
        $this->query->addSelect([
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(dimensions, '$." . $dimension . "')) as " . $dimension),
            DB::raw('AVG(computed) as average_value'),
            DB::raw('COUNT(*) as count'),
            DB::raw('MIN(computed) as min_value'),
            DB::raw('MAX(computed) as max_value')
        ])->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(dimensions, '$." . $dimension . "'))"));

        return $this;
    }

    public function groupByDimensions(array $dimensions): self
    {
        $selects = [];
        $groupBys = [];

        foreach ($dimensions as $dimension) {
            $extractExpr = "JSON_UNQUOTE(JSON_EXTRACT(dimensions, '$." . $dimension . "'))";
            $selects[] = DB::raw("$extractExpr as " . $dimension);
            $groupBys[] = DB::raw($extractExpr);
        }

        $selects = array_merge($selects, [
            DB::raw('AVG(computed) as average_value'),
            DB::raw('COUNT(*) as count'),
            DB::raw('MIN(computed) as min_value'),
            DB::raw('MAX(computed) as max_value')
        ]);

        $this->query->addSelect($selects)->groupBy($groupBys);

        return $this;
    }

    public function groupByTimeWindow(string $interval = '1 hour'): self
    {
        $timeExpr = match ($interval) {
            '1 minute' => "DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i:00')",
            '1 hour' => "DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00')",
            '1 day' => "DATE(timestamp)",
            '1 week' => "DATE(DATE_SUB(timestamp, INTERVAL WEEKDAY(timestamp) DAY))",
            '1 month' => "DATE_FORMAT(timestamp, '%Y-%m-01')",
            default => throw new InvalidArgumentException("Unsupported interval: $interval")
        };

        $this->query->addSelect([
            DB::raw("$timeExpr as time_window"),
            DB::raw('AVG(computed) as average_value'),
            DB::raw('COUNT(*) as count'),
            DB::raw('MIN(computed) as min_value'),
            DB::raw('MAX(computed) as max_value')
        ])->groupBy(DB::raw($timeExpr));

        return $this;
    }

    public function whereInSubquery(string $column, Closure $callback): self
    {
        $subquery = DB::table(self::METRIC_TABLE);
        $callback(new static($subquery));
        $this->query->whereIn($column, $subquery);

        return $this;
    }

    public function wherePercentile(float $percentile, string $direction = '>='): self
    {
        if ($percentile < 0 || $percentile > 100) {
            throw new InvalidArgumentException('Percentile must be between 0 and 100');
        }

        $subquery = DB::table(self::METRIC_TABLE)
            ->select('computed')
            ->orderBy('computed')
            ->limit(1)
            ->offset(floor($this->query->count() * ($percentile / 100)));

        $this->query->where('computed', $direction, $subquery);

        return $this;
    }

    public function withCorrelatedMetrics(string $metricName, float $threshold = 0.7): self
    {
        $this->query->addSelect([
            'm1.name as metric_name',
            'm2.name as correlated_metric',
            DB::raw('CORR(m1.computed, m2.computed) as correlation')
        ])
            ->join(self::METRIC_TABLE . ' as m2', function ($join) {
                $join->on('m1.timestamp', '=', 'm2.timestamp')
                    ->where('m1.name', '<>', 'm2.name');
            })
            ->havingRaw('ABS(CORR(m1.computed, m2.computed)) >= ?', [$threshold])
            ->groupBy('m1.name', 'm2.name');

        return $this;
    }

    public function withChangeRate(): self
    {
        $this->query->addSelect([
            '*',
            DB::raw('(computed - LAG(computed) OVER (ORDER BY timestamp)) / LAG(computed) OVER (ORDER BY timestamp) * 100 as change_rate')
        ]);

        return $this;
    }

    public function joinMetrics(
        string $metricName,
        ?string $alias = null,
        ?Closure $callback = null,
        string $joinType = 'inner'
    ): self {
        $alias = $alias ?? 'm' . (count($this->joins) + 2);
        $table = self::METRIC_TABLE . ' AS ' . $alias;

        $this->query->{$joinType . 'Join'}($table, function ($join) use ($metricName, $alias, $callback) {
            $join->on($this->getMainTableAlias() . '.timestamp', '=', $alias . '.timestamp')
                ->where($alias . '.name', '=', $metricName);

            if ($callback) {
                $callback($join);
            }
        });

        return $this;
    }
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function havingValue(string $operator, float $value): self
    {
        $this->having[] = ['computed', $operator, $value];
        return $this;
    }

    public function get(): Collection
    {
        $this->applyConstraints();
        return $this->query->get()->map(fn ($row) => Metric::from($row));
    }

    public function first(): ?Metric
    {
        $this->applyConstraints();
        $result = $this->query->first();
        return $result ? Metric::from($result) : null;
    }

    public function count(): int
    {
        $this->applyConstraints();
        return $this->query->count();
    }

    public function sum(): float
    {
        $this->applyConstraints();
        return (float) $this->query->sum('computed');
    }

    public function avg(): float
    {
        $this->applyConstraints();
        return (float) $this->query->avg('computed');
    }

    public function min(): float
    {
        $this->applyConstraints();
        return (float) $this->query->min('computed');
    }

    public function max(): float
    {
        $this->applyConstraints();
        return (float) $this->query->max('computed');
    }

    public function aggregate(string $function, array $columns): self
    {
        $allowedFunctions = ['AVG', 'SUM', 'MIN', 'MAX', 'COUNT', 'STDDEV', 'VARIANCE'];

        if (!in_array(strtoupper($function), $allowedFunctions)) {
            throw new InvalidArgumentException("Unsupported aggregate function: $function");
        }

        foreach ($columns as $column) {
            $this->query->addSelect(
                DB::raw("$function($column) as {$function}_$column")
            );
        }

        return $this;
    }

    public function stats(): array
    {
        return [
            'count' => $this->count(),
            'avg' => $this->avg(),
            'min' => $this->min(),
            'max' => $this->max(),
            'stddev' => $this->query->selectRaw('STDDEV(raw_value) as stddev')
                ->value('stddev'),
            'percentiles' => $this->calculatePercentiles(),
            'histogram' => $this->calculateHistogram(),
        ];
    }

    private function calculatePercentiles(): array
    {
        $percentiles = [25, 50, 75, 90, 95, 99];
        $results = [];

        foreach ($percentiles as $p) {
            $results["p$p"] = DB::select(
                "SELECT PERCENTILE_CONT(?) WITHIN GROUP (ORDER BY computed) as p$p 
                FROM ({$this->query->toSql()}) as t",
                [$p / 100]
            )[0]->{"p$p"};
        }

        return $results;
    }


    private function calculateHistogram(int $bins = 10): array
    {
        $stats = $this->query->selectRaw('
            MIN(computed) as min_val,
            MAX(computed) as max_val
        ')->first();

        $binWidth = ($stats->max_val - $stats->min_val) / $bins;
        $histogram = [];

        for ($i = 0; $i < $bins; $i++) {
            $min = $stats->min_val + ($i * $binWidth);
            $max = $min + $binWidth;

            $count = $this->query->clone()
                ->whereBetween('computed', [$min, $max])
                ->count();

            $histogram[] = [
                'bin' => $i + 1,
                'range' => [$min, $max],
                'count' => $count
            ];
        }

        return $histogram;
    }

    private function applyConstraints(): void
    {
        if ($this->name) {
            $this->query->where('name', $this->name);
        }

        if (!empty($this->types)) {
            $this->query->whereIn('type', array_map(fn($type) => $type->value, $this->types));
        }

        if ($this->window) {
            $this->query->where('window', $this->window->value);
        }

        if ($this->timeRange) {
            $this->query->whereBetween('timestamp', [
                $this->timeRange->from,
                $this->timeRange->to
            ]);
        }

        foreach ($this->dimensions as $dimension) {
            $this->query->where('dimensions', 'like', sprintf(
                '%%"%s":"%s"%%',
                $dimension['name'],
                $dimension['value']
            ));
        }

        foreach ($this->orderBy as [$column, $direction]) {
            $this->query->orderBy($column, $direction);
        }

        if ($this->limit) {
            $this->query->limit($this->limit);
        }

        foreach ($this->having as [$column, $operator, $value]) {
            $this->query->having($column, $operator, $value);
        }
    }

    private function getMainTableAlias(): string
    {
        return 'm1';
    }
}
