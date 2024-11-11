<?php

namespace Ninja\DeviceTracker\Modules\Observability\Exceptions;

use Exception;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

class InvalidMetricException extends Exception
{
    public static function invalidType(string $name, MetricType $expected, MetricType $actual): self
    {
        return new self(
            sprintf(
                'Invalid metric type for %s. Expected %s, got %s',
                $name,
                $expected->value,
                $actual->value
            )
        );
    }

    public static function invalidValue(string $name, float $value): self
    {
        return new self(
            sprintf(
                'Invalid value for %s: %f',
                $name,
                $value
            )
        );
    }

    public static function invalidDimensions(string $name, array $invalidDimensions): self
    {
        return new self(
            sprintf(
                'Invalid dimensions for %s: %s',
                $name,
                implode(', ', $invalidDimensions)
            )
        );
    }

    public static function missingRequiredDimensions(string $name, array $missingDimensions): self
    {
        return new self(
            sprintf(
                'Missing required dimensions for %s: %s',
                $name,
                implode(', ', $missingDimensions)
            )
        );
    }

    public static function valueOutOfRange(string $name, float $value, float $min, float $max): self
    {
        return new self(
            sprintf(
                'Value %f is out of range for %s. Expected between %f and %f',
                $value,
                $name,
                $min,
                $max
            )
        );
    }
}
