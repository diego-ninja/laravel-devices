<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;
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
        if (empty($hierarchy)) {
            $hierarchy = [];
        }
        $hierarchy = array_map(fn (string $transport) => self::tryFrom($transport), $hierarchy);
        $hierarchy = array_filter($hierarchy, fn (?self $transport) => ! is_null($transport) && $transport !== self::Request->value);
        if (empty($hierarchy)) {
            $hierarchy = [self::Cookie];
        }

        return $hierarchy[0];
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

    private function fromCookie(): ?StorableId
    {
        $value = Cookie::get(self::parameter());
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $id = null;
        try {
            $id = DeviceIdFactory::from($value);
        } catch (\Throwable) {
        }

        if (! $id instanceof StorableId) {
            try {
                $id = DeviceIdFactory::from($this->decryptCookie($value));
            } catch (\Throwable) {
            }
        }

        return $id;
    }

    private function fromHeader(): ?StorableId
    {
        $value = request()->header(self::parameter());
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return DeviceIdFactory::from($value);
    }

    private function fromSession(): ?StorableId
    {
        $value = Session::get(self::parameter());
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return DeviceIdFactory::from($value);
    }

    private function fromRequest(): ?StorableId
    {
        $value = request()->input(self::parameter());
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return DeviceIdFactory::from($value);
    }
}
