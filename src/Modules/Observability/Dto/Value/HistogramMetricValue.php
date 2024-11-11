<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto\Value;

use InvalidArgumentException;

final class HistogramMetricValue extends AbstractMetricValue
{
    public function __construct(
        float $value,
        array $buckets,
        int $count = 1,
        float $sum = 0
    ) {
        parent::__construct($value, [
            'buckets' => $buckets,
            'count' => $count,
            'sum' => $sum ?: $value
        ]);
    }

    protected function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Histogram value must be non-negative');
        }
        if (empty($this->metadata['buckets'])) {
            throw new InvalidArgumentException('Histogram must have buckets defined');
        }
    }

    public static function empty(): self
    {
        return new self(0.0, []);
    }
}
