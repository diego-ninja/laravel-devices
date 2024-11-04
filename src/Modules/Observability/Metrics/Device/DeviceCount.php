<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Device;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\MetricDefinition;

class DeviceCount extends MetricDefinition
{
    public static function create(): self
    {
        return new self(
            name: MetricName::DeviceUniqueCount,
            type: MetricType::Counter,
            description: 'Total number of devices',
            required_dimensions: [
                'platform',
                'browser',
            ],
            min: 0,
            max: PHP_INT_MAX,
        );
    }
}
