<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto\Value;

use InvalidArgumentException;

final class SummaryMetricValue extends AbstractMetricValue
{
    public function __construct(
        float $value,
        array $quantiles,
        int $count = 1,
        float $sum = 0
    ) {
        parent::__construct($value, [
            'quantiles' => $quantiles,
            'count' => $count,
            'sum' => $sum ?: $value
        ]);
    }

    protected function validate(): void
    {
        if (empty($this->metadata['quantiles'])) {
            throw new InvalidArgumentException('Summary must have quantiles defined');
        }
    }

    public static function empty(): self
    {
        return new self(0.0, []);
    }
}
