<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Contracts;

interface Exportable
{
    public function export(): array;
}