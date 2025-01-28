<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\AbstractStorableIdFactory;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

enum DeviceTransport: string
{
    use Traits\CanTransport;

    case Cookie = 'cookie';
    case Header = 'header';
    case Session = 'session';
    case Request = 'request';

    public static function current(): self
    {
        $hierarchy = config('devices.device_id_transport_hierarchy', [self::Cookie->value]);
        if (empty($hierarchy)) {
            $hierarchy = [self::Cookie->value];
        }

        return self::currentFromHierarchy($hierarchy, self::Cookie);
    }

    public static function responseTransport(): self
    {
        $hierarchy = config('devices.device_id_transport_hierarchy', []);
        return self::getResponseTransport($hierarchy);
    }

    public static function getIdFromHierarchy(): ?StorableId
    {
        $hierarchy = config('devices.device_id_transport_hierarchy', [self::Cookie->value]);
        if (empty($hierarchy)) {
            $hierarchy = [self::Cookie->value];
        }

        return self::storableIdFromHierarchy($hierarchy);
    }

    private static function parameter(): string
    {
        return config('devices.device_id_parameter');
    }

    private static function alternativeParameter(): ?string
    {
        return config('devices.device_id_alternative_parameter');
    }

    /**
     * @return class-string<AbstractStorableIdFactory>
     */
    private static function storableIdFactory(): string
    {
        return DeviceIdFactory::class;
    }
}
