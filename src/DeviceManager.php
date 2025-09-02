<?php

namespace Ninja\DeviceTracker;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DTO\Device as DeviceDTO;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Detection\Contracts\DeviceDetectorInterface;
use Ninja\DeviceTracker\Transports\DeviceTransport;
use Throwable;

use function request;

/**
 * @template TUser of Authenticatable
 */
final class DeviceManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isUserDevice(StorableId $deviceUuid): bool
    {
        return user()?->hasDevice($deviceUuid);
    }

    /**
     * @return Collection<int,Device>
     */
    public function userDevices(): Collection
    {
        return user()?->devices;
    }

    public function tracked(): bool
    {
        return device_uuid() !== null && Device::exists(device_uuid());
    }

    public function fingerprinted(): bool
    {
        return fingerprint() !== null && Device::byFingerprint(fingerprint()) !== null;
    }

    public function track(?StorableId $deviceUuid = null): StorableId
    {
        $deviceUuid ??= device_uuid() ?? DeviceIdFactory::generate();
        DeviceTransport::propagate($deviceUuid);
        event(new DeviceTrackedEvent($deviceUuid));

        return $deviceUuid;
    }

    public function shouldTrack(): bool
    {
        try {
            $withUser = user() !== null
                || in_array(Route::currentRouteName(), config('devices.login_route_names', []));
            if ($withUser) {
                return device_uuid() === null || ! Device::exists(device_uuid());
            } else {
                return self::trackGuestSessions() && (device_uuid() === null || ! Device::exists(device_uuid()));
            }
        } catch (Throwable) {
            return false;
        }
    }

    public function detect(?Request $request = null): ?DeviceDTO
    {
        return app(DeviceDetectorInterface::class)->detect($request ?? request());
    }

    public function isWhitelisted(DeviceDTO|string|null $device): bool
    {
        if ($device === null) {
            return false;
        }

        $userAgent = is_string($device) ? $device : $device->source;

        return in_array($userAgent, config('devices.user_agent_whitelist', []));
    }

    /**
     * @throws UnknownDeviceDetectedException
     */
    public function create(
        ?StorableId $deviceUuid = null,
        ?StorableId $fingerprint = null,
        ?DeviceDTO $deviceDto = null,
    ): Device {
        $deviceUuid ??= device_uuid() ?? DeviceIdFactory::generate();
        $fingerprint ??= fingerprint();
        $deviceDto ??= $this->detect();

        if (! $deviceDto) {
            throw UnknownDeviceDetectedException::withMissingInfo('detection returned null device dto');
        }

        $ua = $deviceDto->source ?? request()->header('User-Agent');

        if (! $deviceDto->valid() && ! $this->isWhitelisted($ua)) {
            throw UnknownDeviceDetectedException::withUA(is_string($ua) ? $ua : 'Unknown');
        }

        return Device::register(
            deviceUuid: $deviceUuid,
            data: $deviceDto,
            fingerprint: $fingerprint,
        );
    }

    public function current(): ?Device
    {
        $fingerprint = fingerprint();
        if ($fingerprint !== null) {
            $device = Device::byFingerprint($fingerprint, false);
            if ($device !== null) {
                return $device;
            }
        }

        $device_uuid = device_uuid();
        if ($device_uuid !== null) {
            $device = Device::byUuid($device_uuid, false);
            if ($device !== null) {
                return $device;
            }
        }

        return null;
    }

    public function matchingDevice(
        ?StorableId $deviceUuid = null,
        ?StorableId $fingerprint = null,
        ?DeviceDTO $deviceDto = null,
    ): ?Device {
        if ($deviceDto === null) {
            return null;
        }

        // Device by uuid and matching info
        if ($deviceUuid !== null) {
            $device = Device::byUuid($deviceUuid);
            if ($device !== null && $device->equals($deviceDto, false)) {
                return $device;
            }
        }

        // Device by fingerprint and matching info
        if ($fingerprint !== null) {
            $device = Device::byFingerprint($fingerprint);
            if ($device !== null && $device->equals($deviceDto, false)) {
                return $device;
            }
        }

        // Device matching dto unique info
        $device = Device::byDeviceDtoUniqueInfo($deviceDto);
        if ($device !== null && $device->equals($deviceDto, false)) {
            return $device;
        }

        return null;
    }

    public static function trackGuestSessions(): bool
    {
        return config('devices.track_guest_sessions') === true;
    }
}
