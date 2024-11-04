<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Tracking\Aggregation\Contracts\EventAggregationRepository;

class DatabaseMetricAggregationRepository implements EventAggregationRepository
{
    public const METRIC_AGGREGATION_TABLE = 'device_metrics';

    public function store(
        MetricName $name,
        MetricType $type,
        float $value,
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
        MetricName $name,
        array $dimensions = [],
        ?AggregationWindow $window = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        $query = DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('name', $name->value);

        if (!empty($dimensions)) {
            foreach ($dimensions as $key => $value) {
                $query->where('dimensions', 'like', "%\"{$key}\":\"{$value}\"%");
            }
        }

        if ($window) {
            $query->where('window', $window);
        }

        if ($from) {
            $query->where('timestamp', '>=', $from);
        }

        if ($to) {
            $query->where('timestamp', '<=', $to);
        }

        return $query->orderBy('timestamp')->get();
    }

    public function prune(AggregationWindow $window, Carbon $before): int
    {
        return DB::table(self::METRIC_AGGREGATION_TABLE)
            ->where('window', $window->value)
            ->where('timestamp', '<', $before)
            ->delete();
    }
}
