<?php

namespace Ninja\DeviceTracker\Modules\Observability\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;
use JsonSerializable;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Stringable;

final readonly class TimeWindow implements JsonSerializable, Stringable
{
    private function __construct(
        public Carbon $from,
        public Carbon $to,
        public int $slot,
        public AggregationWindow $window
    ) {
        if ($this->from->gt($this->to)) {
            throw new InvalidArgumentException('From date must be before or equal to to date');
        }
    }

    public static function forAggregation(AggregationWindow $window, ?Carbon $timestamp = null): self
    {
        $timestamp ??= now();
        $windowSeconds = $window->seconds();
        $slot = floor($timestamp->timestamp / $windowSeconds) * $windowSeconds;

        return new self(
            from: Carbon::createFromTimestamp($slot),
            to: Carbon::createFromTimestamp($slot + $windowSeconds),
            slot: $slot,
            window: $window
        );
    }

    public static function fromSlot(int $slot, AggregationWindow $window): self
    {
        $windowSeconds = $window->seconds();

        return new self(
            from: Carbon::createFromTimestamp($slot),
            to: Carbon::createFromTimestamp($slot + $windowSeconds),
            slot: $slot,
            window: $window
        );
    }

    public function previous(): self
    {
        return self::fromSlot(
            slot: $this->slot - $this->window->seconds(),
            window: $this->window
        );
    }

    public function next(): self
    {
        return self::fromSlot(
            slot: $this->slot + $this->window->seconds(),
            window: $this->window
        );
    }

    public function duration(): int
    {
        return $this->from->diffInSeconds($this->to);
    }

    public function contains(Carbon $timestamp): bool
    {
        return $timestamp->between($this->from, $this->to);
    }

    public function overlaps(self $other): bool
    {
        return $this->from->lte($other->to) && $this->to->gte($other->from);
    }

    public function asTimeRange(): TimeRange
    {
        return new TimeRange(
            from: $this->from,
            to: $this->to
        );
    }

    public function array(): array
    {
        return [
            'from' => $this->from->toDateTimeString(),
            'to' => $this->to->toDateTimeString(),
            'slot' => $this->slot,
            'window' => $this->window->value,
            'duration' => $this->duration()
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        return sprintf(
            '[%s] %s -> %s (slot: %d)',
            $this->window->value,
            $this->from->toDateTimeString(),
            $this->to->toDateTimeString(),
            $this->slot
        );
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function key(string $prefix): string
    {
        return sprintf(
            '%s:*:%s:%d:*',
            $prefix,
            $this->window->value,
            $this->slot
        );
    }
}
