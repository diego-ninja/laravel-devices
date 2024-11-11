<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class DeviceVerificationTimeAverage extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: 'device_verification_time_average',
            type: MetricType::Average,
            description: 'Average time taken for device verification',
            unit: 'seconds',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
                'status',
            ],
            min: 0,
            max: 86400 * 7, // 7 días
        );
    }
}
