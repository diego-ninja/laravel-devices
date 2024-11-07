<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors\Contracts;

use Ninja\DeviceTracker\DTO\Metadata;

interface Processable
{
    public function identifier(): string;
    public function metadata(): Metadata;
}
