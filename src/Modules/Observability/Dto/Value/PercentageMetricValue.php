<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto\Value;

use InvalidArgumentException;

final class PercentageMetricValue extends AbstractMetricValue
{
    public function __construct(
        float $value,
        float $total,
        int $count = 1
    ) {
        parent::__construct($value, [
            'total' => $total,
            'count' => $count,
            'percentage' => $total > 0 ? ($value / $total) * 100 : 0
        ]);
    }

    protected function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Percentage value must be non-negative');
        }

        if (!isset($this->metadata['total']) || $this->metadata['total'] < 0) {
            throw new InvalidArgumentException('Percentage total must be non-negative');
        }

        if ($this->value > $this->metadata['total']) {
            throw new InvalidArgumentException('Percentage value cannot be greater than total');
        }

        if (!isset($this->metadata['count']) || $this->metadata['count'] < 1) {
            throw new InvalidArgumentException('Percentage count must be positive');
        }
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0);
    }

    public function total(): float
    {
        return $this->metadata['total'];
    }

    public function percentage(): float
    {
        return $this->metadata['percentage'];
    }

    public function count(): int
    {
        return $this->metadata['count'];
    }
}
