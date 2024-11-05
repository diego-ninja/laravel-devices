<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\MetricDefinition;

class VerificationLatencyDistribution extends MetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::DeviceVerificationLatency,
            type: MetricType::Summary,
            description: 'Statistical distribution of device verification times',
            unit: 'seconds',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
            ],
            quantiles: [0.5, 0.75, 0.90, 0.95, 0.99],
            min: 0, // 7 días
            max: 86400 * 7 // Ahora sí es soportado por MetricDefinition
        );
    }
}
