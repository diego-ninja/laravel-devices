<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors\Contracts;

interface Processor
{
    public function process(Processable $item): void;
}
