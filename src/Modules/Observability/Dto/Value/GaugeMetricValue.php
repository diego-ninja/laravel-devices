<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto\Value;

use InvalidArgumentException;

final class GaugeMetricValue extends AbstractMetricValue
{
    public function __construct(float $value, ?int $timestamp = null)
    {
        parent::__construct($value, ['timestamp' => $timestamp ?? time()]);
    }

    protected function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Gauge value must be non-negative');
        }
    }

    public static function empty(): self
    {
        return new self(0.0);
    }
}
