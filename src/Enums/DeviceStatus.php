<?php

namespace Ninja\DeviceTracker\Enums;

enum DeviceStatus: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case PartiallyVerified = 'partially_verified';
    case Hijacked = 'hijacked';
    case Inactive = 'inactive';

    /**
     * @return array<int, DeviceStatus>
     */
    public static function values(): array
    {
        return [
            self::Unverified,
            self::PartiallyVerified,
            self::Verified,
            self::Hijacked,
            self::Inactive,
        ];
    }
}
