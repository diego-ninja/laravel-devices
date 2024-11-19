<?php

namespace Ninja\DeviceTracker;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Events\DeviceAttachedEvent;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Detection\Contracts\DeviceDetector;
use Ninja\DeviceTracker\ValueObject\DeviceId;
use Throwable;

use function request;

final class DeviceManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isUserDevice(StorableId $deviceUuid): bool
    {
        return Auth::user()?->hasDevice($deviceUuid);
    }

    public function attach(?StorableId $deviceUuid = null): bool
    {
        $deviceUuid = $deviceUuid ?? device_uuid();

        if (!$deviceUuid) {
            return false;
        }

        if (!Auth::user()) {
            return false;
        }

        if (!Device::exists($deviceUuid)) {
            return false;
        }

        if (Auth::user()->hasDevice($deviceUuid)) {
            return true;
        }

        Auth::user()->devices()->attach($deviceUuid);

        event(new DeviceAttachedEvent(Device::byUuid($deviceUuid), Auth::user()));

        return true;
    }

    public function userDevices(): Collection
    {
        return Auth::user()?->devices;
    }

    public function tracked(): bool
    {
        return device_uuid() && Device::exists(device_uuid());
    }

    public function fingerprinted(): bool
    {
        return fingerprint() && Device::byFingerprint(fingerprint());
    }

    /**
     * @throws DeviceNotFoundException
     */
    public function track(): StorableId
    {
        if (device_uuid()) {
            if (Config::get('devices.regenerate_devices')) {
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
                !Device::exists(device_uuid()) &&
                Config::get('devices.regenerate_devices') && device_uuid();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws UnknownDeviceDetectedException
     */
    public function create(?DeviceId $deviceId = null): Device
    {
        $payload = app(DeviceDetector::class)->detect(request());
        if (!$payload->unknown() || config('devices.allow_unknown_devices')) {
            return Device::register(
                deviceUuid: $deviceId ?? device_uuid(),
                data: $payload
            );
        }

        throw UnknownDeviceDetectedException::withUA(request()->header('User-Agent'));
    }

    public function current(): ?Device
    {
        if (Config::get('devices.fingerprinting_enabled') && fingerprint()) {
            return Device::byFingerprint(fingerprint());
        }

        $device_uuid = device_uuid();
        if ($device_uuid) {
            return Device::byUuid($device_uuid, false);
        }

        return null;
    }
}
