<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto\Value;

use InvalidArgumentException;

final class CounterMetricValue extends AbstractMetricValue
{
    protected function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Counter value must be non-negative');
        }
    }

    public static function empty(): self
    {
        return new self(0.0);
    }
}
