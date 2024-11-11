<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto\Value;

use InvalidArgumentException;

final class AverageMetricValue extends AbstractMetricValue
{
    public function __construct(
        float $value,
        float $sum,
        int $count
    ) {
        parent::__construct($value, [
            'sum' => $sum,
            'count' => $count
        ]);
    }

    protected function validate(): void
    {
        if (!isset($this->metadata['count']) || $this->metadata['count'] < 1) {
            throw new InvalidArgumentException('Average count must be positive');
        }

        if (!isset($this->metadata['sum'])) {
            throw new InvalidArgumentException('Average sum must be provided');
        }

        $calculatedAverage = $this->metadata['sum'] / $this->metadata['count'];
        if (abs($this->value - $calculatedAverage) > 0.000001) { // Usar epsilon para comparación de floats
            throw new InvalidArgumentException('Average value must be sum/count');
        }
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0, 1);
    }

    public function sum(): float
    {
        return $this->metadata['sum'];
    }

    public function count(): int
    {
        return $this->metadata['count'];
    }
}
