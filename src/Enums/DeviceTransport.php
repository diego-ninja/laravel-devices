<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

enum DeviceTransport: string
{
    use Traits\CanTransport;

    private const DEFAULT_REQUEST_PARAMETER = 'internal_device_id';

    case Cookie = 'cookie';
    case Header = 'header';
    case Session = 'session';

    public static function current(): self
    {
        $config = config('devices.device_id_transport', self::Cookie->value);

        return self::tryFrom($config);
    }

    private function parameter(): string
    {
        return config('devices.device_id_parameter');
    }

    private function fromCookie(): ?StorableId
    {
        return Cookie::has($this->parameter()) ? DeviceIdFactory::from(Cookie::get($this->parameter())) : null;
    }

    private function fromHeader(): ?StorableId
    {
        return request()->hasHeader($this->parameter()) ? DeviceIdFactory::from(request()->header($this->parameter())) : null;
    }

    private function fromSession(): ?StorableId
    {
        return Session::has($this->parameter()) ? DeviceIdFactory::from(Session::get($this->parameter())) : null;
    }

    private function fromRequest(): ?StorableId
    {
        return request()->has(self::DEFAULT_REQUEST_PARAMETER) ? DeviceIdFactory::from(request()->input(self::DEFAULT_REQUEST_PARAMETER)) : null;
    }
}
