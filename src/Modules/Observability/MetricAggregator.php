<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use BadMethodCallException;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Average;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Counter;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Gauge;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Histogram;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Rate;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Summary;

/**
 * @method void counter(MetricName $name, float $value = 1, array $dimensions = [])
 * @method void gauge(MetricName $name, float $value, array $dimensions = [])
 * @method void histogram(MetricName $name, float $value, array $dimensions = [])
 * @method void average(MetricName $name, float $value, array $dimensions = [])
 * @method void rate(MetricName $name, float $value, array $dimensions = [])
 * @method void summary(MetricName $name, float $value, array $dimensions = [])
 */
final readonly class MetricAggregator
{
    private string $prefix;

    /**
     * @var AggregationWindow[]
     */
    private array $windows;

    /**
     * @var MetricHandler[]
     */
    private array $handlers;


    public function __construct()
    {
        $this->prefix = config("devices.metrics.aggregation.prefix");
        $this->windows = config("devices.metrics.aggregation.windows", [
            AggregationWindow::Realtime,
            AggregationWindow::Hourly
        ]);

        $this->handlers();
    }

    /**
     * @throws InvalidMetricException
     * @throws MetricHandlerNotFoundException
     */
    public function record(MetricName $name, MetricType $type, float $value, DimensionCollection $dimensions): void
    {
        Registry::validate($name, $type, $value, $dimensions);
        $handler = $this->handlers[$type->value];

        if (!$handler) {
            throw MetricHandlerNotFoundException::forType($type);
        }

        foreach ($this->windows as $window) {
            $this->persist(
                key: new Key(
                    name: $name,
                    type: $type,
                    window: $window,
                    dimensions: $dimensions,
                    prefix: $this->prefix
                ),
                value: $value
            );
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
                MetricType::Rate => $pipe->zadd((string) $key, $timestamp, json_encode([
                    'value' => $value,
                    'timestamp' => $timestamp
                ])),
                MetricType::Average => $pipe->zadd((string) $key, $timestamp, $value)
            };

            $pipe->expire($key, $key->window->seconds() * 2);
        });
    }

    /**
     * @throws MetricHandlerNotFoundException
     * @throws InvalidMetricException
     */
    public function __call($method, $arguments): void
    {
        $name = $arguments[0];
        $type = MetricType::tryFrom($method);
        $dimensions = DimensionCollection::from($arguments[1]);
        $value = (float) $arguments[2];

        if ($type && isset($this->handlers[$type->value])) {
            $this->record($name, $type, $value, $dimensions);
        } else {
            throw new BadMethodCallException(sprintf('Method %s does not exist', $method));
        }
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
