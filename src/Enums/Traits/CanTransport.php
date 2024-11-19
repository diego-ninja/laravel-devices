<?php

namespace Ninja\DeviceTracker\Enums\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

trait CanTransport
{
    public function get(): ?StorableId
    {
        return match ($this) {
            self::Cookie => $this->fromCookie(),
            self::Header => $this->fromHeader(),
        } ?? $this->fromRequest();
    }

    public static function set(mixed $response, StorableId $id): mixed
    {
        if (!$response instanceof Response) {
            return $response;
        }

        $current = self::current();

        return match ($current) {
            self::Cookie => $response->withCookie(
                Cookie::forever(
                    name: $current->parameter(),
                    value: (string) $id,
                    secure: Config::get('session.secure', false),
                    httpOnly: Config::get('session.http_only', true)
                )
            ),
            self::Header => $response->header($current->parameter(), (string) $id)
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

    private function fromRequest(): ?StorableId
    {
        $requestParameter = config('devices.device_id_request_param');
        return request()->has($requestParameter) ? DeviceIdFactory::from(request()->input($requestParameter)) : null;
    }

    public static function propagate(?StorableId $id = null): Request
    {
        $current = self::current();
        $requestParameter = config('devices.device_id_request_param');
        $id = $id ?? $current->get();

        return request()->merge([$requestParameter => (string) $id ?? $current->get()->toString()]);
    }
}
