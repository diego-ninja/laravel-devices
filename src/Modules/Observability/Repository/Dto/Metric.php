<?php

namespace Ninja\DeviceTracker\Modules\Observability\Repository\Dto;

use Carbon\Carbon;
use JsonSerializable;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
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
        public AggregationWindow $window
    ) {
    }

    public static function from(string|array|Metric $data): self
    {
        if ($data instanceof self) {
            return $data;
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self(
            name: MetricName::tryFrom($data['name']),
            type: MetricType::tryFrom($data['type']),
            value: $data['value'],
            timestamp: Carbon::parse($data['timestamp']),
            dimensions: DimensionCollection::from($data['dimensions']),
            window: AggregationWindow::tryFrom($data['window']),
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
            'window' => $this->window->value,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}