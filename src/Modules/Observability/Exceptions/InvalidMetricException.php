<?php

namespace Ninja\DeviceTracker\Modules\Observability\Exceptions;

use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

class InvalidMetricException extends \Exception
{
    public static function invalidType(MetricName $name, MetricType $expected, MetricType $actual): self
    {
        return new self(
            sprintf(
                'Invalid metric type for %s. Expected %s, got %s',
                $name->value,
                $expected->value,
                $actual->value
            )
        );
    }

    public static function invalidDimensions(MetricName $name, array $invalidDimensions): self
    {
        return new self(
            sprintf(
                'Invalid dimensions for %s: %s',
                $name->value,
                implode(', ', $invalidDimensions)
            )
        );
    }

    public static function missingRequiredDimensions(MetricName $name, array $missingDimensions): self
    {
        return new self(
            sprintf(
                'Missing required dimensions for %s: %s',
                $name->value,
                implode(', ', $missingDimensions)
            )
        );
    }

    public static function valueOutOfRange(MetricName $name, float $value, float $min, float $max): self
    {
        return new self(
            sprintf(
                'Value %f is out of range for %s. Expected between %f and %f',
                $value,
                $name->value,
                $min,
                $max
            )
        );
    }
}
