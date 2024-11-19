<?php

namespace Ninja\DeviceTracker\Enums;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;

enum DeviceTransport: string
{
    use Traits\CanTransport;

    case Cookie = 'cookie';
    case Header = 'header';

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
        };
    }
}
