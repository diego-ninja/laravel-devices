<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use BadMethodCallException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\RedisMetricStorage;
use Throwable;

/**
 * @method void counter(MetricName $name, float $value = 1, ?array $dimensions = null)
 * @method void gauge(MetricName $name, float $value, ?array $dimensions = null)
 * @method void histogram(MetricName $name, float $value, ?array $dimensions = null)
 * @method void average(MetricName $name, float $value, ?array $dimensions = null)
 * @method void rate(MetricName $name, float $value, ?array $dimensions = null)
 * @method void summary(MetricName $name, float $value, ?array $dimensions = null)
 */
final readonly class MetricAggregator
{
    private Collection $windows;

    public function __construct(private MetricStorage $storage)
    {
        $this->windows = collect(config("devices.metrics.aggregation.windows", [
            AggregationWindow::Realtime,
            AggregationWindow::Hourly
        ]));
    }

    /**
     * @throws InvalidMetricException
     */
    public function record(
        MetricName $name,
        MetricType $type,
        float $value,
        DimensionCollection $dimensions
    ): void {
        Registry::validate($name, $type, $value, $dimensions);

        foreach ($this->windows as $window) {
            try {
                $this->storage->store(
                    new Key(
                        name: $name,
                        type: $type,
                        window: $window,
                        dimensions: $dimensions
                    ),
                    $value
                );
            } catch (Throwable $e) {
                Log::error('Failed to record metric', [
                    'name' => $name->value,
                    'type' => $type->value,
                    'window' => $window->value,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function persist(Key $key, float $value): void
    {
        Redis::pipeline(function ($pipe) use ($key, $value) {
            $timestamp = now();
            match ($key->type) {
                MetricType::Counter => $pipe->incrbyfloat((string) $key, $value),
                MetricType::Gauge => $pipe->set((string) $key, $value),
                MetricType::Histogram,
                MetricType::Summary,
                MetricType::Rate => $pipe->zadd((string) $key, $timestamp->timestamp, json_encode([
                    'value' => $value,
                    'timestamp' => $timestamp
                ])),
                MetricType::Average => $pipe->zadd((string) $key, $timestamp->timestamp, $value)
            };

            $pipe->expire($key, $key->window->seconds() * 2);
        });
    }

    /**
     * @throws InvalidMetricException
     */
    public function __call(string $method, array $arguments): void
    {
        $type = MetricType::tryFrom($method);
        if (!$type || !HandlerFactory::handlers()->has($type)) {
            throw new BadMethodCallException(
                sprintf('Invalid metric type: %s', $method)
            );
        }

        $name = $arguments[0];
        $value = (float)$arguments[1];
        $dimensions = isset($arguments[2])
            ? DimensionCollection::from($arguments[2])
            : new DimensionCollection();

        $this->record(
            name: $name instanceof MetricName ? $name : MetricName::from($name),
            type: $type,
            value: $value,
            dimensions: $dimensions
        );
    }

    public function windows(): Collection
    {
        return $this->windows;
    }

    public function enabled(AggregationWindow $window): bool
    {
        return $this->windows->contains($window);
    }
}
