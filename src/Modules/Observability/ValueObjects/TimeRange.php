<?php

namespace Ninja\DeviceTracker\Modules\Observability\ValueObjects;

use Carbon\Carbon;

final readonly class TimeRange
{
    public function __construct(
        public Carbon $from,
        public Carbon $to
    ) {
        if ($this->from->gt($this->to)) {
            throw new \InvalidArgumentException('From date must be before or equal to to date');
        }
    }

    public static function last(string $period): self
    {
        $to = now();
        return new self(
            now()->sub($period),
            $to
        );
    }

    public static function today(): self
    {
        return new self(
            now()->startOfDay(),
            now()->endOfDay()
        );
    }

    public static function thisWeek(): self
    {
        return new self(
            now()->startOfWeek(),
            now()->endOfWeek()
        );
    }

    public static function thisMonth(): self
    {
        return new self(
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }
}