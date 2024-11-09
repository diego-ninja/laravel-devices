<?php

namespace Ninja\DeviceTracker\Modules\Observability\Enums;

enum Bucket: string
{
    case Milliseconds = 'milliseconds';
    case Seconds = 'seconds';
    case Minutes = 'minutes';
    case Score = 'score';
    case Percentage = 'percentage';
    case Bytes = 'bytes';

    case Default = 'default';

    public function scale(): array
    {
        return match ($this) {
            self::Milliseconds => [1, 5, 10, 50, 100, 500, 1000, 5000],
            self::Seconds =>[0.01, 0.05, 0.1, 0.5, 1, 2.5, 5, 10],
            self::Minutes => [0.1, 1, 10, 100, 1000],
            self::Score => [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9],
            self::Percentage => [0.1, 1, 10, 100],
            self::Bytes => [1024, 1024 * 1024, 10 * 1024 * 1024, 100 * 1024 * 1024],
            self::Default => [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000],
        };
    }
}
