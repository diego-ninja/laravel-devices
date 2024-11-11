<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\AbstractMetricDefinition;

class RiskScoreAverage extends AbstractMetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: 'device_risk_score_average',
            type: MetricType::Gauge,
            description: 'Average risk score of devices',
            unit: 'score',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
                'status',
            ],
            min: 0,
            max: 100,
        );
    }
}
