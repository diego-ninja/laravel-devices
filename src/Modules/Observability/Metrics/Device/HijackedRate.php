<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\MetricDefinition;

class HijackedRate extends MetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::HijackedDeviceRate,
            type: MetricType::Gauge,
            description: 'Rate of hijacked devices vs total devices',
            unit: 'percentage',
            required_dimensions: ['platform_family'],
            allowed_dimensions: [
                'browser_family',
                'device_type',
            ],
            min: 0,
            max: 100,
        );
    }
}
