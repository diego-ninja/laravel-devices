<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\PercentageMetricValue;

final class Percentage extends AbstractMetricHandler
{
    public function compute(array $values): MetricValue
    {
        $this->validateOrFail($values);

        if (empty($values)) {
            return PercentageMetricValue::empty();
        }

        $partialSum = 0;
        $totalSum = 0;

        foreach ($values as $value) {
            $partialSum += $value['value'];
            $totalSum += $value['metadata']['total'] ?? $value['value'];
        }

        return new PercentageMetricValue(
            value: $partialSum,
            total: $totalSum,
            count: count($values)
        );
    }

    public function validate(array $values): bool
    {
        if (!parent::validate($values)) {
            return false;
        }

        try {
            foreach ($values as $value) {
                $total = $value['metadata']['total'] ?? $value['value'];
                if ($total < 0 || $value['value'] > $total) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
