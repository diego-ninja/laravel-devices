<?php

namespace Ninja\DeviceTracker\Modules\Observability\Contracts;

interface MetricValue
{
    public function value(): float;
    public function metadata(): array;
    public function serialize(): string;
    public static function empty(): self;
}
