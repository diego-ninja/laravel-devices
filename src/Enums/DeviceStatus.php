<?php

namespace Ninja\DeviceTracker\Enums;

enum DeviceStatus: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case Hijacked = 'hijacked';
    case Inactive = 'inactive';

    public static function values(): array
    {
        return [
            self::Unverified,
            self::Verified,
            self::Hijacked,
            self::Inactive,
        ];
    }
}
