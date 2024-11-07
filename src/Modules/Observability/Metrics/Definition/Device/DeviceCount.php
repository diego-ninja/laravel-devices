<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class DeviceCount extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::DeviceCount,
            type: MetricType::Gauge,
            description: 'Total number of devices in the system',
            required_dimensions: [],
            allowed_dimensions: [
                'platform_family',
                'browser_family',
                'device_type',
            ],
            min: 0,
            max: PHP_INT_MAX,
        );
    }
}
