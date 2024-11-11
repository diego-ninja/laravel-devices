<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\AverageMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;

final class Average extends AbstractMetricHandler
{
    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        if (empty($values)) {
            return AverageMetricValue::empty();
        }

        $sum = 0;
        $count = 0;

        foreach ($values as $value) {
            if (
                isset($value['metadata']['count']) && isset($value['metadata']['sum'])
            ) {
                $sum += $value['metadata']['sum'];
                $count += $value['metadata']['count'];
            } else {
                $sum += $value['value'];
                $count++;
            }
        }

        if ($count === 0) {
            throw new InvalidMetricException('Cannot compute average with zero values');
        }

        return new AverageMetricValue(
            value: $sum / $count,
            sum: $sum,
            count: $count
        );
    }

    public function validate(array $values): bool
    {
        if (!parent::validate($values)) {
            return false;
        }

        try {
            foreach ($values as $value) {
                if (isset($value['metadata'])) {
                    if (isset($value['metadata']['count']) && $value['metadata']['count'] < 1) {
                        return false;
                    }
                    if (isset($value['metadata']['sum']) && !is_numeric($value['metadata']['sum'])) {
                        return false;
                    }
                }
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
