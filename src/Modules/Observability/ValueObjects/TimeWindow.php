<?php

namespace Ninja\DeviceTracker\Modules\Observability\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;
use JsonSerializable;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Stringable;

final readonly class TimeWindow implements JsonSerializable, Stringable
{
    private function __construct(
        public Carbon $from,
        public Carbon $to,
        public int $slot,
        public Aggregation $aggregation
    ) {
        if ($this->from->gt($this->to)) {
            throw new InvalidArgumentException('From date must be before or equal to to date');
        }
    }

    public static function forAggregation(Aggregation $aggregation, ?Carbon $timestamp = null): self
    {
        $timestamp ??= now();
        $windowSeconds = $aggregation->seconds();
        $slot = floor($timestamp->timestamp / $windowSeconds) * $windowSeconds;

        return new self(
            from: Carbon::createFromTimestamp($slot),
            to: Carbon::createFromTimestamp($slot + $windowSeconds),
            slot: $slot,
            aggregation: $aggregation
        );
    }

    public static function fromSlot(int $slot, Aggregation $aggregation): self
    {
        $windowSeconds = $aggregation->seconds();

        return new self(
            from: Carbon::createFromTimestamp($slot),
            to: Carbon::createFromTimestamp($slot + $windowSeconds),
            slot: $slot,
            aggregation: $aggregation
        );
    }

    public function previous(): self
    {
        return self::forAggregation($this->aggregation->previous());
    }

    public function next(): ?self
    {
        $aggregation = $this->aggregation->next();
        if ($aggregation === null) {
            return null;
        }

        return self::forAggregation($aggregation);
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

    public static function from(string|array $data): self
    {
        return new self(
            from: Carbon::parse($data['from']),
            to: Carbon::parse($data['to']),
            slot: $data['slot'],
            aggregation: Aggregation::tryFrom($data['aggregation'])
        );
    }

    public function array(): array
    {
        return [
            'from' => $this->from->toDateTimeString(),
            'to' => $this->to->toDateTimeString(),
            'slot' => $this->slot,
            'aggregation' => $this->aggregation->value,
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
            $this->aggregation->value,
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
            $this->aggregation->value,
            $this->slot
        );
    }
}
