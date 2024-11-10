<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository\Dto;

use Carbon\Carbon;
use JsonSerializable;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

readonly class Metric implements JsonSerializable
{
    public function __construct(
        public MetricName $name,
        public MetricType $type,
        public float|array $value,
        public Carbon $timestamp,
        public DimensionCollection $dimensions,
        public Aggregation $aggregation
    ) {
    }

    public function fingerprint(): string
    {
        return hash('sha256', sprintf(
            '%s:%s:%s:%s:%s',
            $this->name->value,
            $this->type->value,
            $this->dimensions->implode(':'),
            $this->aggregation->value,
            $this->timestamp->toIso8601String(),
        ));
    }

    public static function from(string|array|\stdClass|Metric $data): self
    {
        if ($data instanceof self) {
            return $data;
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if ($data instanceof \stdClass) {
            $data = (array) $data;
        }

        return new self(
            name: MetricName::tryFrom($data['name']),
            type: MetricType::tryFrom($data['type']),
            value: $data['value'],
            timestamp: Carbon::parse($data['timestamp']),
            dimensions: DimensionCollection::from(json_decode($data['dimensions'], true)),
            aggregation: Aggregation::tryFrom($data['window']),
        );
    }

    public function array(): array
    {
        return [
            'name' => $this->name->value,
            'type' => $this->type->value,
            'value' => $this->value,
            'timestamp' => $this->timestamp->format(DATE_ATOM),
            'dimensions' => $this->dimensions->array(),
            'aggregation' => $this->aggregation->value,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
