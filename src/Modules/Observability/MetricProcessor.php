<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Log;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Repository\DatabaseMetricAggregationRepository;
use Throwable;

final class MetricProcessor
{
    private const METRIC_TYPES = [
        MetricType::Counter,
        MetricType::Gauge,
        MetricType::Histogram
    ];

    private string $prefix;

    private Collection $keys;
    public function __construct(private readonly DatabaseMetricAggregationRepository $repository)
    {
        $this->prefix = config("devices.metrics.aggregation.prefix");
        $this->keys = collect();
    }

    public function window(AggregationWindow $window): void
    {
        try {
            $now = now();
            $windowSeconds = $window->seconds();
            $previousSlot = floor($now->subSeconds($windowSeconds)->timestamp / $windowSeconds) * $windowSeconds;

            foreach (self::METRIC_TYPES as $type) {
                $this->metricType($type, $window, $previousSlot);
            }

            $this->clean();
            $this->success($window, $previousSlot);
        } catch (Throwable $e) {
            $this->failure($e, $window);
        }
    }

    private function metricType(MetricType $type, AggregationWindow $window, int $timeSlot): void
    {
        $pattern = $this->pattern($type, $window, $timeSlot);
        Log::info('Processing metric pattern', [
            'pattern' => $pattern,
        ]);
        $keys = Redis::keys($pattern);
        foreach ($keys as $key) {
            try {
                $this->metric($key, $type, $window, $timeSlot);
                $this->keys->add($key);
            } catch (Throwable $e) {
                Log::error('Failed to process metric', [
                    'key' => $key,
                    'type' => $type,
                    'window' => $window->value,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    private function metric(string $key, MetricType $type, AggregationWindow $window, int $timeSlot): void
    {
        $metadata = $this->parseKey($key);
        if (!$metadata) {
            return;
        }

        $value = $this->value($key, $type);
        if ($value === null) {
            return;
        }

        $dimensions = $this->dimensions($metadata['dimensions']);

        $this->repository->store(
            name: $metadata['name'],
            type: $type,
            value: $value,
            dimensions: $dimensions,
            timestamp: Carbon::createFromTimestamp($timeSlot),
            window: $window
        );
    }

    private function value(string $key, MetricType $type): ?float
    {
        try {
            return match ($type) {
                MetricType::Counter,
                MetricType::Gauge => (float) Redis::get($key),
                MetricType::Histogram => $this->histogram($key),
                MetricType::Summary,
                MetricType::Average,
                MetricType::Rate => throw new Exception('To be implemented')
            };
        } catch (Throwable $e) {
            Log::error('Failed to get metric value', [
                'key' => $key,
                'type' => $type->value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function dimensions(string $dimensionString): array
    {
        $dimensions = [];
        $pairs = explode(':', $dimensionString);

        foreach ($pairs as $pair) {
            [$key, $value] = array_pad(explode('=', $pair), 2, null);
            if ($key && $value !== null) {
                $dimensions[$key] = $value;
            }
        }

        return $dimensions;
    }

    private function histogram(string $key): float
    {
        $values = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    private function parseKey(string $key): ?array
    {
        $pattern = '/^' . preg_quote($this->prefix) . ':' .
            '([^:]+):' .
            '(' . implode('|', MetricType::values()) . '):' .
            '(' . implode('|', AggregationWindow::values()) . '):' .
            '(\d+):' .
            '(.+)$/';


        if (!preg_match($pattern, $key, $matches)) {
            Log::warning('Invalid metric key format', ['key' => $key]);
            return null;
        }

        return [
            'name' => MetricName::tryFrom($matches[1]),
            'type' => MetricType::tryFrom($matches[2]),
            'window' => AggregationWindow::tryFrom($matches[3]),
            'timestamp' => (int) $matches[3],
            'dimensions' => $matches[4]
        ];
    }

    private function pattern(MetricType $type, AggregationWindow $window, int $slot): string
    {
        return sprintf(
            '%s:*:%s:%s:%d:*',
            $this->prefix,
            $type->value,
            $window->value,
            $slot
        );
    }

    private function clean(): void
    {
        if (!empty($this->keys)) {
            Redis::pipeline(function ($pipe) {
                $this->keys->keys()->each(fn($key) => $pipe->del($key));
            });

            $this->keys = collect();
        }
    }

    private function success(AggregationWindow $window, int $timeSlot): void
    {
        Log::info('Successfully processed aggregation window', [
            'window' => $window,
            'time_slot' => Carbon::createFromTimestamp($timeSlot)->toDateTimeString(),
            'metrics_processed' => $this->keys->count()
        ]);

        Redis::set(
            sprintf('%s:last_processing:%s', $this->prefix, $window->value),
            now()->timestamp
        );
    }

    /**
     * @throws Throwable
     */
    private function failure(Throwable $e, AggregationWindow $window): void
    {
        Log::error('Failed to process aggregation window', [
            'window' => $window,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        Redis::incr(
            sprintf('%s:processing_errors:%s', $this->prefix, $window->value)
        );

        throw $e;
    }

    public function time(AggregationWindow $window): ?Carbon
    {
        $timestamp = Redis::get(
            sprintf('%s:last_processing:%s', $this->prefix, $window->value)
        );

        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public function errorCount(AggregationWindow $window): int
    {
        return (int)Redis::get(
            sprintf('%s:processing_errors:%s', $this->prefix, $window->value)
        ) ?? 0;
    }

    public function reset(AggregationWindow $window): void
    {
        Redis::del(sprintf('%s:processing_errors:%s', $this->prefix, $window->value));
    }
}
