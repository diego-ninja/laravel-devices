<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

enum Transport: string
{
    case Cookie = 'cookie';
    case Header = 'header';

    public static function current(): self
    {
        $config = config('devices.device_id.transport', self::Cookie->value);
        return self::tryFrom($config);
    }

    public function get(): ?StorableId
    {
        return match ($this) {
            self::Cookie => $this->fromCookie(),
            self::Header => $this->fromHeader(),
        };
    }

    public function parameter(): string
    {
        return match ($this) {
            self::Cookie => config('devices.device_id_cookie_name'),
            self::Header => config('devices.device_id_header_name'),
        };
    }

    public static function set(Response $response, StorableId $deviceId): Response
    {
        $current = self::current();

        return match ($current) {
            self::Cookie => $response->withCookie(
                Cookie::forever(
                    name: $current->parameter(),
                    value: (string) $$deviceId,
                    secure: Config::get('session.secure', false),
                    httpOnly: Config::get('session.http_only', true)
                )
            ),
            self::Header => $response->header($current->parameter(), (string) $deviceId)
        };
    }

    private function fromCookie(): ?StorableId
    {
        $cookieName = config('devices.device_id_cookie_name');
        return Cookie::has($cookieName) ? DeviceIdFactory::from(Cookie::get($cookieName)) : null;
    }

    private function fromHeader(): ?StorableId
    {
        $headerName = config('devices.device_id_header_name');
        return request()->hasHeader($headerName) ? DeviceIdFactory::from(request()->header($headerName)) : null;
    }

    public static function propagate(Request $request, ?StorableId $deviceId = null): Request
    {
        $current = self::current();
        $requestParameter = config('devices.device_id_request_param');

        return $request->merge([$requestParameter => (string) $deviceId ?? $current->get()->toString()]);
    }
}
