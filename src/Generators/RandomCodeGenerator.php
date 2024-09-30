<?php

namespace Ninja\DeviceTracker\Generators;

use Ninja\DeviceTracker\Contracts\CodeGenerator;
use Random\RandomException;

final readonly class RandomCodeGenerator implements CodeGenerator
{
    /**
     * @throws RandomException
     */
    public function generate(): int
    {
        return random_int(100000, 999999);
    }
}
