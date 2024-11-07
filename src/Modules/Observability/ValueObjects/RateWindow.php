<?php

namespace Ninja\DeviceTracker\Modules\Observability\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class RateWindow implements JsonSerializable, Stringable
{
    private function __construct(
        public int $start,
        public int $end,
        public array $values,
        public int $interval,
        public array $metadata = []
    ) {
        if ($this->start > $this->end) {
            throw new InvalidArgumentException('Start timestamp must be before or equal to end timestamp');
        }

        if ($this->interval <= 0) {
            throw new InvalidArgumentException('Interval must be greater than zero');
        }
    }

    public static function fromValues(array $values, int $interval): self
    {
        if (empty($values)) {
            $now = time();
            return new self(
                start: $now,
                end: $now,
                values: [],
                interval: $interval
            );
        }

        $timestamps = array_map(
            fn($v) => $v['timestamp'] ?? time(),
            $values
        );

        return new self(
            start: min($timestamps),
            end: max($timestamps),
            values: $values,
            interval: $interval
        );
    }

    public static function fromTimestamps(int $start, int $end, int $interval, array $metadata = []): self
    {
        return new self(
            start: $start,
            end: $end,
            values: [],
            interval: $interval,
            metadata: $metadata
        );
    }

    public function duration(): int
    {
        return $this->end - $this->start;
    }

    public function empty(): bool
    {
        return empty($this->values);
    }

    public function calculate(array $validValues): array
    {
        if ($this->empty() || $this->duration() <= 0) {
            return [
                'rate' => (float) count($validValues),
                'count' => count($validValues),
                'interval' => $this->interval,
                'window_start' => $this->start,
                'window_end' => $this->end,
                'metadata' => $this->metadata
            ];
        }

        return [
            'rate' => (count($validValues) * $this->interval) / $this->duration(),
            'count' => count($validValues),
            'interval' => $this->interval,
            'window_start' => $this->start,
            'window_end' => $this->end,
            'metadata' => $this->metadata
        ];
    }

    public function merge(self $other): self
    {
        return new self(
            start: min($this->start, $other->start),
            end: max($this->end, $other->end),
            values: array_merge($this->values, $other->values),
            interval: $this->interval,
            metadata: array_merge($this->metadata, $other->metadata)
        );
    }

    public function start(): Carbon
    {
        return Carbon::createFromTimestamp($this->start);
    }

    public function end(): Carbon
    {
        return Carbon::createFromTimestamp($this->end);
    }

    public function array(): array
    {
        return [
            'start' => $this->start()->toDateTimeString(),
            'end' => $this->end()->toDateTimeString(),
            'duration' => $this->duration(),
            'interval' => $this->interval,
            'value_count' => count($this->values),
            'metadata' => $this->metadata
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        return sprintf(
            'Rate[%ds] %s -> %s (%d values)',
            $this->interval,
            $this->start()->toDateTimeString(),
            $this->end()->toDateTimeString(),
            count($this->values)
        );
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
