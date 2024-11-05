<?php

namespace Ninja\DeviceTracker\Modules\Observability\Exceptions;

use Exception;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

class MetricHandlerNotFoundException extends Exception
{
    public static function forType(MetricType $type): self
    {
        return new self(
            sprintf(
                'Metric handler for type %s not found',
                $type->value
            )
        );
    }
}
