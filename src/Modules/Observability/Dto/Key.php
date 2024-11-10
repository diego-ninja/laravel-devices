<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto;

use JsonSerializable;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Stringable;

final class Key implements JsonSerializable, Stringable
{
    public function __construct(
        public MetricName          $name,
        public MetricType          $type,
        public Aggregation         $window,
        public DimensionCollection $dimensions,
        public ?int                $slot = null,
        public ?string             $prefix = null
    ) {
        $this->slot = $this->slot ?? $this->window->timeslot(now());
        $this->prefix = $this->prefix ?? config('devices.observability.prefix');
    }

    public static function decode(string $key): self
    {
        $parts = explode(":", $key);
        if (self::prefixed($parts)) {
            return new self(
                name: MetricName::from($parts[1]),
                type: MetricType::from($parts[2]),
                window: Aggregation::from($parts[3]),
                dimensions: DimensionCollection::from($parts[5]),
                slot: (int) $parts[4]
            );
        }

        return new self(
            name: MetricName::from($parts[0]),
            type: MetricType::from($parts[1]),
            window: Aggregation::from($parts[2]),
            dimensions: DimensionCollection::from($parts[4]),
            slot: (int) $parts[3],
        );
    }

    public function array(): array
    {
        return [
            'name' => $this->name->value,
            'type' => $this->type->value,
            'window' => $this->window->value,
            'dimensions' => $this->dimensions->array(),
            'slot' => $this->slot
        ];
    }

    public function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        return new self(
            name: MetricName::from($data['name']),
            type: MetricType::from($data['type']),
            window: Aggregation::from($data['window']),
            dimensions: DimensionCollection::from($data['dimensions']),
            slot: $data['slot']
        );
    }

    public function asTimeWindow(): TimeWindow
    {
        return TimeWindow::fromSlot(
            slot: $this->slot,
            aggregation: $this->window
        );
    }

    public static function prefixed(array $parts): bool
    {
        return count($parts) === 6;
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        if ($this->prefix) {
            return sprintf(
                "%s:%s:%s:%s:%d:%s",
                $this->prefix,
                $this->name->value,
                $this->type->value,
                $this->window->value,
                $this->slot,
                $this->dimensions
            );
        }

        return sprintf(
            "%s:%s:%s:%d:%s",
            $this->name->value,
            $this->type->value,
            $this->window->value,
            $this->slot,
            $this->dimensions
        );
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
