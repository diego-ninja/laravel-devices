<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Traits\HandlesMetricValues;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Validators\MetricValueValidator;

abstract class AbstractMetricHandler implements MetricHandler
{
    use HandlesMetricValues;

    protected float $min;
    protected float $max;
    protected bool $allowNegative;

    public function __construct(?float $min = null, ?float $max = null, bool $allowNegative = false)
    {
        $this->min = $min ?? PHP_FLOAT_MIN;
        $this->max = $max ?? PHP_FLOAT_MAX;
        $this->allowNegative = $allowNegative;
    }


    abstract public function compute(array $values): float|array;
    abstract public function merge(array $windows): float|array;
    public function validate(float $value): bool
    {
        return MetricValueValidator::validate(
            value: $value,
            min: $this->min,
            max: $this->max,
            allowNegative: $this->allowNegative
        );
    }

    protected function filter(array $values): array
    {
        return array_filter(
            $this->normalize($values),
            fn($value) => $this->validate($value)
        );
    }
}
