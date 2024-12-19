<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\SessionIdFactory;

enum SessionTransport: string
{
    use Traits\CanTransport;

    private const DEFAULT_REQUEST_PARAMETER = 'internal_session_id';

    case Cookie = 'cookie';
    case Header = 'header';
    case Session = 'session';

    public static function current(): ?self
    {
        $config = config('devices.session_id_transport', self::Cookie->value);

        return self::tryFrom($config);
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

    private function parameter(): string
    {
        return config('devices.session_id_parameter');
    }

    private function fromCookie(): ?StorableId
    {
        $value = Cookie::get($this->parameter());
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
        $value = request()->header($this->parameter());
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
        $value = Session::get($this->parameter());
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
        $value = request()->input(self::DEFAULT_REQUEST_PARAMETER);
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return SessionIdFactory::from($value);
    }
}
