<?php

namespace Ninja\DeviceTracker\Facades;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\DTO\Device as DeviceDto;

/**
 * @method static Device|null current()
 * @method static bool isUserDevice(StorableId $deviceUuid)
 * @method static Collection<int,Device> userDevices()
 * @method static bool attach(?StorableId $deviceUuid = null)
 * @method static Device create(?StorableId $deviceId = null)
 * @method static StorableId track()
 * @method static bool tracked()
 * @method static bool fingerprinted()
 * @method static bool shouldRegenerate()
 * @method static DeviceDto|null detect()
 * @method static bool isWhitelisted(string $userAgent)
 */
final class DeviceManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'device_manager';
    }
}
