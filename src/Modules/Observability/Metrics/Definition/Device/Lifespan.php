<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class Lifespan extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: 'device_lifespan',
            type: MetricType::Average,
            description: 'Average lifespan of devices',
            unit: 'days',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
                'status',
            ],
            min: 0,
            max: 365 * 5
        );
    }
}
