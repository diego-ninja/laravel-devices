<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;

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

    public function increment(MetricName $name, array $dimensions, float $value = 1, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        foreach ($this->windows as $window) {
            $slot = $this->timeslot($timestamp, $window);
            $key = $this->key($name->value, $dimensions, $window, $slot);

            Redis::pipeline(function ($pipe) use ($key, $value, $window) {
                $pipe->incrbyfloat($key, $value);
                $pipe->expire($key, $window->seconds());
            });
        }
    }

    public function update(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        foreach ($this->windows as $window) {
            $timeSlot = $this->timeslot($timestamp, $window);
            $key = $this->key($name->value, $dimensions, $window, $timeSlot);

            Redis::pipeline(function ($pipe) use ($key, $value, $window) {
                $pipe->set($key, $value);
                $pipe->expire($key, $window->seconds() * 2);
            });
        }
    }

    public function record(MetricName $name, array $dimensions, float $value, ?Carbon $timestamp = null): void
    {
        $timestamp = $timestamp ?? now();

        foreach ($this->windows as $window) {
            $timeSlot = $this->timeslot($timestamp, $window);
            $key = $this->key($name->value, $dimensions, $window, $timeSlot);

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
        string $name,
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
            $name,
            $window,
            $timeSlot,
            $dimensionString
        );
    }

}