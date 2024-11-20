<?php

namespace Ninja\DeviceTracker\Contracts;

use Stringable;

interface StorableId extends Stringable
{
    public static function from(StorableId|string $id): StorableId;

    public static function build(): StorableId;
}
