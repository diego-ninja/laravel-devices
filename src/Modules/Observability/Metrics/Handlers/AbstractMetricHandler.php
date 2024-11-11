<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricHandler;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Traits\HandlesMetricValues;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Validators\MetricValueValidator;

abstract class AbstractMetricHandler implements MetricHandler
{
    public function validate(array $values): bool
    {
        try {
            foreach ($values as $value) {
                if (!isset($value['value']) || !is_numeric($value['value'])) {
                    return false;
                }
                if ($value['value'] < 0 && !$this->allowsNegative()) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function allowsNegative(): bool
    {
        return false;
    }

    /**
     * @throws InvalidMetricException
     */
    protected function validateOrFail(array $values): void
    {
        if (!$this->validate($values)) {
            throw new InvalidMetricException('Invalid metric values');
        }
    }
}