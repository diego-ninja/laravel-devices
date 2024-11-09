<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors\Items;

use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

final readonly class Metric implements Processable
{
    public function __construct(
        private Key $key,
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
            'key' => (string) $this->key,
            'type' => $this->key->type->value,
            'window' => $this->window->array()
        ]);
    }

    public function key(): Key
    {
        return $this->key;
    }

    public function window(): TimeWindow
    {
        return $this->window;
    }
}
