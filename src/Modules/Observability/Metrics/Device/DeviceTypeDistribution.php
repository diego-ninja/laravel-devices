<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\MetricDefinition;

class DeviceTypeDistribution extends MetricDefinition
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