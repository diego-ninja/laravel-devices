<?php

namespace Ninja\DeviceTracker\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Finished = 'finished';
    case Blocked = 'blocked';
    case Locked = 'locked';

    /**
     * @return array<int, SessionStatus>
     */
    public static function values(): array
    {
        return [
            self::Active,
            self::Inactive,
            self::Finished,
            self::Blocked,
            self::Locked,
        ];
    }
}
