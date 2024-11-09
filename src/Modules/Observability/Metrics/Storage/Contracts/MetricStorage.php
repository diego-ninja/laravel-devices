<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts;

use Carbon\Carbon;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

interface MetricStorage
{
    public function store(Key $key, float $value): void;
    public function value(Key $key): array;
    public function keys(string $pattern): array;
    public function delete(TimeWindow|array $keys): void;
    public function expired(TimeWindow $window): bool;
    public function prune(AggregationWindow $window, Carbon $before): int;
    public function count(AggregationWindow $window): array;
    public function health(): array;
}