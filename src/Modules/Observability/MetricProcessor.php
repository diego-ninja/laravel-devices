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
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Average;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Counter;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Gauge;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Histogram;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Rate;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Summary;
use Ninja\DeviceTracker\Modules\Observability\Repository\DatabaseMetricAggregationRepository;
use Throwable;

final class MetricProcessor
{
    private string $prefix;

    private Collection $keys;

    private array $handlers;
    public function __construct(private readonly DatabaseMetricAggregationRepository $repository)
    {
        $this->prefix = config("devices.metrics.aggregation.prefix");
        $this->keys = collect();

        $this->handlers();
    }

    public function window(AggregationWindow $window): void
    {
        try {
            $now = now();
            $windowSeconds = $window->seconds();
            $slot = floor($now->subSeconds($windowSeconds)->timestamp / $windowSeconds) * $windowSeconds;

            foreach (MetricType::all() as $type) {
                $this->metricType($type, $window, $slot);
            }

            if ($window !== AggregationWindow::Realtime) {
                $this->merge($window, $slot);
            }

            $this->success($window, $slot);
            $this->clean();
        } catch (Throwable $e) {
            $this->failure($e, $window);
        }
    }

    public function merge(AggregationWindow $window, int $slot): void
    {
        $previous = $window->previous();
        if ($previous === null) {
            return;
        }

        $from = Carbon::createFromTimestamp($slot);
        $to = Carbon::createFromTimestamp($slot + $window->seconds());

        $metrics = $this->repository->query(
            name: null,
            window: $previous,
            from: $from,
            to: $to
        );

        foreach ($metrics->groupBy(['name', 'type', 'dimensions']) as $name => $byType) {
            foreach ($byType as $type => $byDimensions) {
                foreach ($byDimensions as $dimensions => $values) {
                    $handler = $this->handlers[$type];
                    $merged = $handler->merge($values->pluck('value')->toArray());

                    $this->repository->store(
                        name: MetricName::from($name),
                        type: MetricType::from($type),
                        value: $this->format($merged),
                        dimensions: json_decode($dimensions, true),
                        timestamp: Carbon::createFromTimestamp($slot),
                        window: $window
                    );
                }
            }
        }
    }

    private function metricType(MetricType $type, AggregationWindow $window, int $timeSlot): void
    {
        $keys = Redis::keys($this->pattern($type, $window, $timeSlot));

        Log::info('Processing metric type', [
            'type' => $type->value,
            'window' => $window->value,
            'time_slot' => Carbon::createFromTimestamp($timeSlot)->toDateTimeString(),
            'keys' => $keys
        ]);

        foreach ($keys as $key) {
            try {
                $this->metric($key, $type, $window, $timeSlot);
                $this->keys->add($key);
            } catch (Throwable $e) {
                Log::error('Failed to process metric', [
                    'key' => $key,
                    'type' => $type->value,
                    'window' => $window->value,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    private function metric(string $key, MetricType $type, AggregationWindow $window, int $timeSlot): void
    {
        $metadata = $this->parse($key);
        if (!$metadata) {
            return;
        }

        $value = $this->value($key, $type);
        $computed = $this->handlers[$type->value]->compute($value);

        try {
            $dimensions = $this->dimensions($metadata['dimensions']);

            $this->repository->store(
                name: $metadata['name'],
                type: $type,
                value: $this->format($computed),
                dimensions: $dimensions,
                timestamp: Carbon::createFromTimestamp($timeSlot),
                window: $window
            );
        } catch (Throwable $e) {
            Log::error('Failed to store metric', [
                'key' => $key,
                'type' => $type->value,
                'window' => $window->value,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function value(string $key, MetricType $type): array
    {
        $key = str_replace(config('database.redis.options.prefix'), '', $key);

        try {
            $values = match ($type) {
                MetricType::Counter,
                MetricType::Gauge => [['value' => (float) Redis::get($key)]],
                MetricType::Histogram => $this->histogram($key),
                MetricType::Summary => $this->summary($key),
                MetricType::Average => $this->average($key),
                MetricType::Rate => $this->timestamped($key)
            };

            return $this->handlers[$type->value]->compute($values);
        } catch (Throwable $e) {
            Log::error('Failed to get metric value', [
                'key' => $key,
                'type' => $type->value,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function histogram(string $key): array
    {
        $values = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        return array_map(fn($value) => ['value' => (float) $value], $values);
    }

    private function summary(string $key): array
    {
        $values = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $value => $score) {
            $decoded = json_decode($value, true);
            if (isset($decoded['value'])) {
                $result[] = [
                    'value' => (float) $decoded['value'],
                    'timestamp' => (int) $score,
                ];
            }
        }

        return $result;
    }

    private function average(string $key): array
    {
        $values = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        return array_map(fn($value) => ['value' => (float) $value], $values);
    }

    private function timestamped(string $key): array
    {
        $rawValues = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($rawValues)) {
            return [];
        }

        $values = [];
        foreach ($rawValues as $value => $timestamp) {
            $decoded = json_decode($value, true);
            if (isset($decoded['value'])) {
                $values[] = [
                    'value' => $decoded['value'],
                    'timestamp' => $timestamp
                ];
            }
        }

        return $values;
    }

    private function format(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_float($value)) {
            return number_format($value, 4, '.', '');
        }

        return (string) $value;
    }

    /**
     * @throws Exception
     */
    private function dimensions(string $dimensionString): array
    {
        $dimensions = [];
        $pairs = explode(':', $dimensionString);

        if (count($pairs) % 2 !== 0) {
            throw new Exception('Invalid dimension string. Uneven number of key-value pairs');
        }

        for ($i = 0; $i < count($pairs); $i += 2) {
            $key = $pairs[$i];
            $value = $pairs[$i + 1];
            $dimensions[$key] = $value;
        }

        return $dimensions;
    }

    private function parse(string $key): ?array
    {
        Log::info('Parsing metric key', ['key' => $key]);
        $pattern = '/^[^:]+:' .
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
            'timestamp' => (int) $matches[4],
            'dimensions' => $matches[5]
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
            'window' => $window->value,
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
            'window' => $window->value,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        Redis::incr(
            sprintf('%s:processing_errors:%s', $this->prefix, $window->value)
        );
    }

    public function time(AggregationWindow $window): ?Carbon
    {
        $timestamp = Redis::get(
            sprintf('%s:last_processing:%s', $this->prefix, $window->value)
        );

        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public function errors(AggregationWindow $window): int
    {
        return (int) Redis::get(
            sprintf('%s:processing_errors:%s', $this->prefix, $window->value)
        ) ?? 0;
    }

    public function reset(AggregationWindow $window): void
    {
        Redis::del(sprintf('%s:processing_errors:%s', $this->prefix, $window->value));
    }

    private function handlers(): void
    {
        $this->handlers = [
            MetricType::Counter->value => new Counter(),
            MetricType::Gauge->value => new Gauge(),
            MetricType::Histogram->value => new Histogram(
                config('devices.metrics.buckets')
            ),
            MetricType::Average->value => new Average(),
            MetricType::Rate->value => new Rate(),
            MetricType::Summary->value => new Summary()
        ];
    }
}
