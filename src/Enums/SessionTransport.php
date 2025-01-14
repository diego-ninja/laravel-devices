<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\SessionIdFactory;

enum SessionTransport: string
{
    use Traits\CanTransport;

    case Cookie = 'cookie';
    case Header = 'header';
    case Session = 'session';
    case Request = 'request';

    public static function current(): self
    {
        $hierarchy = config('devices.session_id_transport_hierarchy', [self::Cookie->value]);
        if (empty($hierarchy)) {
            $hierarchy = [self::Cookie->value];
        }

        return self::currentFromHierarchy($hierarchy, self::Cookie);
    }

    public static function getIdFromHierarchy(): ?StorableId
    {
        $hierarchy = config('devices.session_id_transport_hierarchy', [self::Cookie->value]);
        if (empty($hierarchy)) {
            $hierarchy = [self::Cookie->value];
        }

        return self::storableIdFromHierarchy($hierarchy);
    }

    public static function forget(): void
    {
        $current = self::current();
        if ($current === null) {
            return;
        }

        match ($current) {
            self::Cookie => Cookie::queue(Cookie::forget($current->parameter())),
            self::Header => null,
            self::Session => Session::forget($current->parameter()),
        };
    }

    private static function parameter(): string
    {
        return config('devices.session_id_parameter');
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

        return SessionIdFactory::from($value);
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

        return SessionIdFactory::from($value);
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

        return SessionIdFactory::from($value);
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

        return SessionIdFactory::from($value);
    }
}
