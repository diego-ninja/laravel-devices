<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class VerifiedPercentage extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::VerifiedDevicePercentage,
            type: MetricType::Gauge,
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
