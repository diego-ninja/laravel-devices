<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\AbstractMetricDefinition;

class PlatformDistribution extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::DevicePlatformDistribution,
            type: MetricType::Counter,
            description: 'Distribution of devices across different platforms',
            required_dimensions: [
                'platform_family',
                'platform_version',
            ],
            allowed_dimensions: [
                'status',
                'device_type',
            ],
            min: 0,
            max: PHP_INT_MAX,
        );
    }
}
