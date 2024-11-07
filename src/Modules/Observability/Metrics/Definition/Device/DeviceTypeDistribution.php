<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class DeviceTypeDistribution extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::DeviceTypeDistribution,
            type: MetricType::Counter,
            description: 'Distribution of devices across different types',
            required_dimensions: [
                'device_type',
                'device_family',
            ],
            allowed_dimensions: [
                'platform_family',
                'status',
            ],
            min: 0,
            max: PHP_INT_MAX,
        );
    }
}
