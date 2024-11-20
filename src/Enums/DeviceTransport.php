<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

enum DeviceTransport: string
{
    use Traits\CanTransport;

    case Cookie = 'cookie';
    case Header = 'header';

    case Session = 'session';

    public static function current(): self
    {
        $config = config('devices.device_id_transport', self::Cookie->value);
        return self::tryFrom($config);
    }

    public function parameter(): string
    {
        return match ($this) {
            self::Cookie => config('devices.device_id_cookie_name'),
            self::Header => config('devices.device_id_header_name'),
            self::Session => config('devices.device_id_session_name'),
        };
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

    private function requestParameter(): string
    {
        return config('devices.device_id_request_param');
    }

    private function fromRequest(): ?StorableId
    {
        $requestParameter = $this->requestParameter();
        return request()->has($requestParameter) ? DeviceIdFactory::from(request()->input($requestParameter)) : null;
    }
}
