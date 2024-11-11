<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts;

use Carbon\Carbon;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

interface MetricStorage
{
    public function store(Key $key, MetricValue $value): void;
    public function value(Key $key): MetricValue;
    public function keys(string $pattern): array;
    public function delete(TimeWindow|array $keys): void;
    public function expired(TimeWindow $window): bool;
    public function prune(Aggregation $window, Carbon $before): int;
    public function count(Aggregation $window): array;
    public function health(): array;
}