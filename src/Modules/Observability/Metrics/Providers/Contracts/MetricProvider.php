<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Providers\Contracts;

interface MetricProvider
{
    public function register(): void;
}