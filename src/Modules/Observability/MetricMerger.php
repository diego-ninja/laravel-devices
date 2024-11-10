<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Illuminate\Support\Collection;
use Log;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final readonly class MetricMerger
{
    public function __construct(
        private MetricAggregationRepository $repository
    ) {
    }

    /**
     * @throws Throwable
     */
    public function merge(TimeWindow $window): void
    {
        $previous = $window->previous();
        $metrics = $this->repository->findByTimeRange($previous->asTimeRange());
        if ($metrics->isEmpty()) {
            return;
        }

        $this->mergeGroups($this->groupMetrics($metrics), $window);
    }

    public function store(
        MetricName $name,
        MetricType $type,
        mixed $value,
        DimensionCollection $dimensions,
        TimeWindow $timeWindow
    ): void {
        try {
            $this->repository->store(
                name: $name,
                type: $type,
                value: $value,
                dimensions: $dimensions,
                timestamp: $timeWindow->from,
                window: $timeWindow->aggregation
            );
        } catch (Throwable $e) {
            Log::error('Failed to store metric', [
                'name' => $name->value,
                'type' => $type->value,
                'timeWindow' => $timeWindow->array(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function groupMetrics(Collection $metrics): Collection
    {
        return $metrics->groupBy(function (Metric $metric) {
            return sprintf(
                '%s:%s:%s',
                $metric->name->value,
                $metric->type->value,
                $metric->dimensions->json()
            );
        });
    }

    /**
     * @throws Throwable
     */
    private function mergeGroups(Collection $groups, TimeWindow $timeWindow): void
    {
        foreach ($groups as $groupKey => $groupMetrics) {
            [$name, $type, $dimensionsJson] = explode(':', $groupKey);

            try {
                $this->mergeMetricValues(
                    name: MetricName::from($name),
                    type: MetricType::from($type),
                    dimensions: json_decode($dimensionsJson, true),
                    values: $groupMetrics->toArray(),
                    timeWindow: $timeWindow
                );
            } catch (Throwable $e) {
                Log::error('Failed to merge metric group', [
                    'group' => $groupKey,
                    'timeWindow' => $timeWindow->array(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * @throws Throwable
     */
    private function mergeMetricValues(
        MetricName $name,
        MetricType $type,
        array $dimensions,
        array $values,
        TimeWindow $timeWindow
    ): void {
        try {
            $processedValues = array_map(
                fn($metric) => $this->prepareValueForMerge($type, $metric['value']),
                $values
            );

            $merged = HandlerFactory::merge($type, $processedValues);

            $this->store(
                name: $name,
                type: $type,
                value: $merged,
                dimensions: new DimensionCollection($dimensions),
                timeWindow: $timeWindow
            );
        } catch (Throwable $e) {
            Log::error('Failed to merge metric values', [
                'name' => $name->value,
                'type' => $type->value,
                'timeWindow' => $timeWindow->array(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function prepareValueForMerge(MetricType $type, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (in_array($type, [MetricType::Summary, MetricType::Histogram])) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return (float) $value;
    }
}
