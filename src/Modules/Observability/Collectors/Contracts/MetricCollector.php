<?php

namespace Ninja\DeviceTracker\Modules\Observability\Collectors\Contracts;

interface MetricCollector
{
    public function collect(): void;
    public function listen(): void;
}