<?php

namespace Ninja\DeviceTracker\Modules\Observability\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class TimeRange implements JsonSerializable, Stringable
{
    public function __construct(
        public Carbon $from,
        public Carbon $to
    ) {
        if ($this->from->gt($this->to)) {
            throw new InvalidArgumentException('From date must be before or equal to to date');
        }
    }

    public static function last(string $period): self
    {
        return new self(
            from: now()->sub($period),
            to: now()
        );
    }

    public static function today(): self
    {
        return new self(
            from: now()->startOfDay(),
            to: now()->endOfDay()
        );
    }

    public static function week(): self
    {
        return new self(
            from: now()->startOfWeek(),
            to: now()->endOfWeek()
        );
    }

    public static function month(): self
    {
        return new self(
            from: now()->startOfMonth(),
            to: now()->endOfMonth()
        );
    }

    public function year(): self
    {
        return new self(
            from: now()->startOfYear(),
            to: now()->endOfYear()
        );
    }

    public function duration(): int
    {
        return $this->to->diffInSeconds($this->from);
    }

    public function contains(Carbon $timestamp): bool
    {
        return $timestamp->between($this->from, $this->to);
    }

    public function overlaps(self $other): bool
    {
        return $this->from->lte($other->to) && $this->to->gte($other->from);
    }

    public function equals(self $other): bool
    {
        return $this->from->equalTo($other->from) && $this->to->equalTo($other->to);
    }

    public function array(): array
    {
        return [
            'from' => $this->from->toDateTimeString(),
            'to' => $this->to->toDateTimeString(),
            'duration' => $this->duration()
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s -> %s',
            $this->from->toDateTimeString(),
            $this->to->toDateTimeString()
        );
    }
}
