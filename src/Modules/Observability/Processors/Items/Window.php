<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors\Items;

use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

final readonly class Window implements Processable
{
    public function __construct(
        private TimeWindow $window
    ) {
    }
    public function identifier(): string
    {
        return sprintf(
            'window:%s:%d',
            $this->window->window->value,
            $this->window->slot
        );
    }

    public function metadata(): Metadata
    {
        return new Metadata([
            'window' => $this->window->array()
        ]);
    }

    public function window(): TimeWindow
    {
        return $this->window;
    }
}
