<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Enums\Quantile;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class VerificationLatencyDistribution extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: 'device_verification_latency_distribution',
            type: MetricType::Summary,
            description: 'Statistical distribution of device verification times',
            unit: 'seconds',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
            ],
            quantiles: Quantile::scale(),
            min: 0,
            max: 86400 * 7
        );
    }
}
