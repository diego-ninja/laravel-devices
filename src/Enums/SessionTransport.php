<?php

namespace Ninja\DeviceTracker\Enums;

use Ninja\DeviceTracker\Contracts\StorableId;

enum SessionTransport: string
{
    use Traits\CanTransport;

    case Cookie = 'cookie';
    case Header = 'header';

    public static function current(): self
    {
        $config = config('devices.session_id_transport', self::Cookie->value);
        return self::tryFrom($config);
    }

    public function get(): ?StorableId
    {
        return match ($this) {
            self::Cookie => $this->fromCookie(),
            self::Header => $this->fromHeader(),
        } ?? $this->fromRequest();
    }

    public function parameter(): string
    {
        return match ($this) {
            self::Cookie => config('devices.session_id_cookie_name'),
            self::Header => config('devices.session_id_header_name'),
        };
    }
}
