<?php

namespace Ninja\DeviceTracker\Modules\Observability\Contracts;

use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;

interface MetricHandler
{
    /**
     * @param array<array{value: float, timestamp: int, metadata?: array}> $values
     * @throws InvalidMetricException
     */
    public function compute(array $values): MetricValue;

    /**
     * @param array<array{value: float, timestamp: int, metadata?: array}> $values
     */
    public function validate(array $values): bool;
}
