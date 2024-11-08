<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts;

use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;

interface StateStorage
{
    public function get(string $key): ?string;
    public function set(string $key, string $value, ?int $ttl = null): void;
    public function increment(string $key): int;
    public function delete(string $key): void;
    public function state(AggregationWindow $window): array;
    public function clean(): int;
    public function health(): array;
    public function pipeline(callable $callback): array;
    public function batch(array $operations): void;

}