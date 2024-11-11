<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class VerifiedDevicePercentage extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: 'verified_device_percentage',
            type: MetricType::Percentage,
            description: 'Percentage of verified devices vs total devices',
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
