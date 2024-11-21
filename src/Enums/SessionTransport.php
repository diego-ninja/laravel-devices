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

    public static function current(): self
    {
        $config = config('devices.session_id_transport', self::Cookie->value);

        return self::tryFrom($config);
    }

    public static function forget(): void
    {
        match (self::current()) {
            self::Cookie => Cookie::queue(Cookie::forget(self::current()->parameter())),
            self::Header => null,
            self::Session => Session::forget(self::current()->parameter()),
        };
    }

    private function parameter(): string
    {
        return config('devices.session_id_parameter');
    }

    private function fromCookie(): ?StorableId
    {
        return Cookie::has($this->parameter()) ? SessionIdFactory::from(Cookie::get($this->parameter())) : null;
    }

    private function fromHeader(): ?StorableId
    {
        return request()->hasHeader($this->parameter()) ? SessionIdFactory::from(request()->header($this->parameter())) : null;
    }

    private function fromSession(): ?StorableId
    {

        return Session::has($this->parameter()) ? SessionIdFactory::from(Session::get($this->parameter())) : null;
    }

    private function fromRequest(): ?StorableId
    {
        return request()->has(self::DEFAULT_REQUEST_PARAMETER) ? SessionIdFactory::from(request()->input(self::DEFAULT_REQUEST_PARAMETER)) : null;
    }
}
