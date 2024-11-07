<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors\Items;

use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

final readonly class Metric implements Processable
{
    public function __construct(
        private string $key,
        private MetricType $type,
        private TimeWindow $window
    ) {
    }

    public function identifier(): string
    {
        return $this->key;
    }

    public function metadata(): Metadata
    {
        return new Metadata([
            'key' => $this->key,
            'type' => $this->type->value,
            'window' => $this->window->array()
        ]);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function type(): MetricType
    {
        return $this->type;
    }

    public function window(): TimeWindow
    {
        return $this->window;
    }
}
