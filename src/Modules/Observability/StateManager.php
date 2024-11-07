<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

class StateManager
{
    public function __construct(
        private string $prefix = ''
    ) {
        $this->prefix = $prefix ?: config('devices.metrics.aggregation.prefix');
    }

    public function success(TimeWindow $timeWindow): void
    {
        Redis::set(
            $this->getLastProcessingKey($timeWindow->window),
            $timeWindow->from->timestamp
        );
    }

    public function error(AggregationWindow $window): void
    {
        Redis::incr($this->getErrorCountKey($window));
    }

    public function last(AggregationWindow $window): ?Carbon
    {
        $timestamp = Redis::get($this->getLastProcessingKey($window));
        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public function errors(AggregationWindow $window): int
    {
        return (int) Redis::get($this->getErrorCountKey($window)) ?? 0;
    }

    public function reset(AggregationWindow $window): void
    {
        Redis::del($this->getErrorCountKey($window));
    }

    private function getLastProcessingKey(AggregationWindow $window): string
    {
        return sprintf('%s:last_processing:%s', $this->prefix, $window->value);
    }

    private function getErrorCountKey(AggregationWindow $window): string
    {
        return sprintf('%s:processing_errors:%s', $this->prefix, $window->value);
    }
}
