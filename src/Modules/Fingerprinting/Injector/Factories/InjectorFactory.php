<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Factories;

use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\ClientJSInjector;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Contracts\Injector;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Enums\Library;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\FingerprintJSInjector;

final class InjectorFactory
{
    private static array $injectors = [
        FingerprintJSInjector::class,
        ClientJSInjector::class
    ];

    public static function make(Library $library): Injector
    {
        foreach (self::$injectors as $injectorClass) {
            if ($injectorClass::library() === $library) {
                return new $injectorClass();
            }
        }

        throw new InvalidArgumentException(sprintf('Injector for library %s not found', $library->value));
    }
}