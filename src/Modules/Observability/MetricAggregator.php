<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use BadMethodCallException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;
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
 * @method void counter(MetricName $name, array $dimensions, float $value = 1, ?Carbon $timestamp = null)
 * @method void gauge(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null)
 * @method void histogram(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null)
 * @method void average(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null)
 * @method void rate(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null)
 * @method void summary(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null)
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
    public function record(MetricName $name, MetricType $type, array $dimensions, float $value, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        Registry::validate($name, $type, $value, $dimensions);
        $handler = $this->handlers[$type->value];

        if (!$handler) {
            throw MetricHandlerNotFoundException::forType($type);
        }

        foreach ($this->windows as $window) {
            $timeSlot = $this->timeslot($timestamp, $window);
            $key = $this->key($name, $type, $dimensions, $window, $timeSlot);

            $this->persist($key, $type, $value, $window, $timestamp);
        }
    }

    private function persist(string $key, MetricType $type, float $value, AggregationWindow $window, Carbon $timestamp): void
    {
        Redis::pipeline(function ($pipe) use ($key, $type, $value, $window, $timestamp) {
            match ($type) {
                MetricType::Counter => $pipe->incrbyfloat($key, $value),
                MetricType::Gauge => $pipe->set($key, $value),
                MetricType::Histogram,
                MetricType::Summary,
                MetricType::Rate => $pipe->zadd($key, $timestamp->timestamp, json_encode([
                    'value' => $value,
                    'timestamp' => $timestamp->timestamp
                ])),
                MetricType::Average => $pipe->zadd($key, $timestamp->timestamp, $value)
            };

            $pipe->expire($key, $window->seconds() * 2);
        });
    }

    /**
     * @throws MetricHandlerNotFoundException
     * @throws InvalidMetricException
     */
    public function __call($name, $arguments): void
    {
        $type = MetricType::tryFrom($name);
        if ($type && isset($this->handlers[$type->value])) {
            $timestamp = $arguments[3] ?? now();
            $this->record($arguments[0], $type, $arguments[1], $arguments[2], $timestamp);
        } else {
            throw new BadMethodCallException(sprintf('Method %s does not exist', $name));
        }
    }

    private function timeslot(Carbon $timestamp, AggregationWindow $window): int
    {
        $seconds = $window->seconds();
        return floor($timestamp->timestamp / $seconds) * $seconds;
    }

    private function key(
        MetricName $name,
        MetricType $type,
        array $dimensions,
        AggregationWindow $window,
        int $timeSlot
    ): string {
        $dimensionString = collect($dimensions)
            ->map(fn($value, $key) => "{$key}:{$value}")
            ->join(':');

        return sprintf(
            "%s:%s:%s:%s:%d:%s",
            $this->prefix,
            $name->value,
            $type->value,
            $window->value,
            $timeSlot,
            $dimensionString
        );
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
