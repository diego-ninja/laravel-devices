<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts;

interface MetricExporter
{
    public function export(): string;
}