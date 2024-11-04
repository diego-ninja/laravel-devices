<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;

final readonly class MetricAggregator
{
    private string $prefix;

    /**
     * @var AggregationWindow[]
     */
    private array $windows;

    public function __construct()
    {
        $this->prefix = config("devices.metrics.aggregation.prefix");
        $this->windows = config("devices.metrics.aggregation.windows", [
            AggregationWindow::Realtime,
            AggregationWindow::Hourly
        ]);
    }

    public function counter(MetricName $name, array $dimensions, float $value = 1, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        Registry::validate($name, MetricType::Counter, $value, $dimensions);

        foreach ($this->windows as $window) {
            $slot = $this->timeslot($timestamp, $window);
            $key = $this->key($name, $dimensions, $window, $slot);

            Redis::pipeline(function ($pipe) use ($key, $value, $window) {
                $pipe->incrbyfloat($key, $value);
                $pipe->expire($key, $window->seconds());
            });
        }
    }

    public function gauge(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        Registry::validate($name, MetricType::Gauge, $value, $dimensions);

        foreach ($this->windows as $window) {
            $timeSlot = $this->timeslot($timestamp, $window);
            $key = $this->key($name, $dimensions, $window, $timeSlot);

            Redis::pipeline(function ($pipe) use ($key, $value, $window) {
                $pipe->set($key, $value);
                $pipe->expire($key, $window->seconds() * 2);
            });
        }
    }

    public function histogram(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        Registry::validate($name, MetricType::Histogram, $value, $dimensions);

        foreach ($this->windows as $window) {
            $timeSlot = $this->timeslot($timestamp, $window);
            $key = $this->key($name, $dimensions, $window, $timeSlot);

            Redis::pipeline(function ($pipe) use ($key, $value, $window) {
                $pipe->zadd($key, $value, $value);
                $pipe->expire($key, $window->seconds() * 2);
            });
        }
    }

    private function timeslot(Carbon $timestamp, AggregationWindow $window): int
    {
        $seconds = $window->seconds();
        return floor($timestamp->timestamp / $seconds) * $seconds;
    }

    private function key(
        MetricName $name,
        array $dimensions,
        string $window,
        int $timeSlot
    ): string {
        $dimensionString = collect($dimensions)
            ->map(fn($value, $key) => "{$key}:{$value}")
            ->join(':');

        return sprintf(
            "%s:%s:%s:%s:%s",
            $this->prefix,
            $name->value,
            $window,
            $timeSlot,
            $dimensionString
        );
    }

}