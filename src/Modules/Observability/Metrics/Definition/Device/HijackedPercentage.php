<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class HijackedPercentage extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: 'hijacked_device_percentage',
            type: MetricType::Gauge,
            description: 'Percentage of hijacked devices vs total devices',
            unit: '%',
            allowed_dimensions: [
                'browser_family',
                'device_type',
            ],
            min: 0,
            max: 100,
        );
    }
}
