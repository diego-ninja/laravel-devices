<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class RiskScoreDistribution extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::DeviceRiskScore,
            type: MetricType::Summary,
            description: 'Statistical distribution of device risk scores',
            unit: 'score',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
                'status',
            ],
            quantiles: [0.5, 0.75, 0.90, 0.95, 0.99],
            min: 0,
            max: 100,
        );
    }
}
