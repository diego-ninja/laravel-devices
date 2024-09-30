<?php

namespace Ninja\DeviceTracker\Contracts;

use Stringable;

interface StorableId extends Stringable
{
    public static function fromString(string $id): StorableId;
}
