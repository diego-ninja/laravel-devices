<?php

namespace Ninja\DeviceTracker\Modules\Observability\Contracts;

interface MetricHandler
{
    public function compute(array $values): float|array;
    public function merge(array $windows): float|array;
    public function validate(float $value): bool;
}
