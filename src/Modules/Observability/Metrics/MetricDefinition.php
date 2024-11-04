<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics;

use Illuminate\Contracts\Support\Arrayable;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;

class MetricDefinition implements Arrayable
{
    private array $buckets;
    private array $labels;

    public function __construct(
        private readonly MetricName $name,
        private readonly MetricType $type,
        private readonly string $description,
        private readonly string $unit = '',
        private readonly array $options = [],
        array $labels = [],
        array $buckets = []
    ) {
        $this->labels = array_merge(['device_uuid', 'session_uuid'], $labels);
        $this->buckets = match ($type) {
            MetricType::Histogram => $buckets ?: $this->defaultBuckets(),
            default => []
        };
    }

    public function name(): string
    {
        return $this->name->value;
    }

    public function type(): MetricType
    {
        return $this->type;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function labels(): array
    {
        return $this->labels;
    }

    public function buckets(): array
    {
        return $this->buckets;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name->value,
            'type' => $this->type->value,
            'description' => $this->description,
            'unit' => $this->unit,
            'labels' => $this->labels,
            'buckets' => $this->buckets,
            'options' => $this->options
        ];
    }

    private function defaultBuckets(): array
    {
        return match ($this->unit) {
            'seconds' => [0.01, 0.05, 0.1, 0.5, 1, 2.5, 5, 10],
            'milliseconds' => [1, 5, 10, 50, 100, 500, 1000, 5000],
            'bytes' => [1024, 1024 * 1024, 10 * 1024 * 1024, 100 * 1024 * 1024],
            'score' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9],
            default => [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000]
        };
    }
}
