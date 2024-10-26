<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Factories;

use InvalidArgumentException;

final class InjectorFactory
{
    public static function make(string $library): string
    {
        $class = 'Ninja\DeviceTracker\Modules\Fingerprinting\Injector\\' . ucfirst($library) . 'Injector';

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Injector for library {$library} not found");
        }

        return $class;
    }
}