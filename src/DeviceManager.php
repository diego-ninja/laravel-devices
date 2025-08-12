<?php

namespace Ninja\DeviceTracker;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DTO\Device as DeviceDTO;
use Ninja\DeviceTracker\Events\DeviceAttachedEvent;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Detection\Contracts\DeviceDetector;
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

    public function attach(?StorableId $deviceUuid = null): bool
    {
        $deviceUuid = $deviceUuid ?? device_uuid();

        if ($deviceUuid === null) {
            return false;
        }

        if (user() === null) {
            return false;
        }

        if (! Device::exists($deviceUuid)) {
            return false;
        }

        if (user()->hasDevice($deviceUuid)) {
            return true;
        }

        user()->devices()->attach($deviceUuid);

        $device = Device::byUuid($deviceUuid);
        if ($device === null) {
            return false;
        }

        event(new DeviceAttachedEvent($device, user()));

        return true;
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

    /**
     * @throws DeviceNotFoundException
     */
    public function track(): StorableId
    {
        if (device_uuid() !== null) {
            if (config('devices.regenerate_devices') === true) {
                event(new DeviceTrackedEvent(device_uuid()));
                DeviceTransport::propagate(device_uuid());

                return device_uuid();
            } else {
                throw new DeviceNotFoundException('Tracked device not found in database');
            }
        } else {
            $deviceUuid = DeviceIdFactory::generate();
            DeviceTransport::propagate($deviceUuid);
            event(new DeviceTrackedEvent($deviceUuid));

            return $deviceUuid;
        }
    }

    public function shouldRegenerate(): bool
    {
        try {
            return
                device_uuid() !== null &&
                ! Device::exists(device_uuid()) &&
                config('devices.regenerate_devices') === true;
        } catch (Throwable) {
            return false;
        }
    }

    public function detect(): ?DeviceDTO
    {
        return app(DeviceDetector::class)->detect(request());
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
    public function create(?StorableId $deviceUuid = null): ?Device
    {
        $payload = $this->detect();
        if (! $payload) {
            return null;
        }

        $ua = request()->header('User-Agent');

        if ($payload->valid() || $this->isWhitelisted($ua)) {
            $deviceUuid = $deviceUuid ?? device_uuid();
            if ($deviceUuid !== null) {
                return Device::register(
                    deviceUuid: $deviceUuid,
                    data: $payload
                );
            }

            return null;
        }

        if (is_string($ua)) {
            throw UnknownDeviceDetectedException::withUA($ua);
        }

        throw UnknownDeviceDetectedException::withUA('Unknown');
    }

    public function current(): ?Device
    {
        if (config('devices.fingerprinting_enabled') === true && fingerprint() !== null) {
            return Device::byFingerprint(fingerprint());
        }

        $device_uuid = device_uuid();
        if ($device_uuid !== null) {
            return Device::byUuid($device_uuid, false);
        }

        return null;
    }

    public function userDevicesTableEnabled(): bool
    {
        return config('devices.user_devices.enabled', true);
    }
}
