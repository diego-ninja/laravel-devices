<?php

namespace Ninja\DeviceTracker\Modules\Observability\Contracts;

use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;

interface Dimensionable
{
    public function dimensions(): DimensionCollection;
}
