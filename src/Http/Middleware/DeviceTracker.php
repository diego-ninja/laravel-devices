<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Contracts\LoggedUserGuesser;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DTO\Device as DeviceDto;
use Ninja\DeviceTracker\Enums\Transport;
use Ninja\DeviceTracker\Exception\InvalidDeviceDetectedException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Transports\DeviceTransport;
use Throwable;

final readonly class DeviceTracker
{
    /**
     * Process an incoming HTTP request to detect a device, match it against existing records,
     * optionally create and track a new device, and propagate the device UUID to downstream handlers.
     *
     * Applies optional custom transport hierarchy and response transport, enforces whitelist/allowance
     * policies for unknown or bot devices, attempts to locate a matching Device (with optional IP/user checks),
     * handles race conditions during device creation, and ensures a device UUID is available for propagation.
     *
     * @param string|null $hierarchyParameterString Pipe-separated list of transport names to override the configured device transport hierarchy; when null or empty the configured hierarchy is used.
     * @param string|null $responseTransport Optional transport name to override the configured device response transport for device_id; when null the configured response transport is used.
     * @param bool $skipDeviceMatchCheck When true, skip device match validation that would update existing device information.
     *
     * @throws UnknownDeviceDetectedException When an unknown device is detected and exceptions are configured to be thrown.
     * @throws InvalidDeviceDetectedException When a bot/invalid device is detected and exceptions are configured to be thrown.
     *
     * @return mixed A response produced by the next middleware, wrapped by DeviceTransport so the propagated device UUID is available to downstream code.
     */
    public function handle(
        Request $request,
        Closure $next,
        ?string $hierarchyParameterString = null,
        ?string $responseTransport = null,
        bool $skipDeviceMatchCheck = false,
    ): mixed {
        $this->checkCustomDeviceTransportHierarchy($hierarchyParameterString);
        $this->checkCustomDeviceResponseTransport($responseTransport);

        // detect all device details
        $detectedDevice = DeviceManager::detect($request);

        // Check whitelist and allowance of detected device
        if (! $detectedDevice || ! DeviceManager::isWhitelisted($detectedDevice->source)) {
            if (! $detectedDevice || $detectedDevice->unknown()) {
                $this->isDeviceAllowed(
                    userAgent: $detectedDevice->source ?? null,
                );
            } elseif ($detectedDevice->bot()) {
                $this->isDeviceAllowed(
                    unknown: false,
                    userAgent: $detectedDevice->source,
                );
            }
        }

        $fingerprint = fingerprint();
        $deviceUuid = device_uuid();
        $ip = $request->getClientIp();
        $user = user() ?? $this->guessLoggingUser();
        $checkIp = DeviceManager::isLoginRoute() || $user !== null;

        $device = $this->getMatchingDevice(
            deviceUuid: $deviceUuid,
            fingerprint: $fingerprint,
            deviceDto: $detectedDevice,
            ckeckIpAndUser: $checkIp,
            ip: $ip,
            user: $user,
            skipDeviceMatchCheck: $skipDeviceMatchCheck,
        );

        if ($device !== null) {
            return DeviceTransport::set($next(DeviceTransport::propagate($device->uuid)), $device->uuid);
        } elseif ($deviceUuid !== null && Device::byUuid($deviceUuid) !== null) {
            // Request has a device uuid that belongs to an existing device that do not match info. Reset device uuid
            $deviceUuid = DeviceIdFactory::generate();
            DeviceTransport::propagate($deviceUuid);
        }

        if (DeviceManager::shouldTrack()) {
            try {
                $device = DeviceManager::create(
                    deviceUuid: $deviceUuid,
                    fingerprint: $fingerprint,
                    deviceDto: $detectedDevice,
                );
            } catch (UniqueConstraintViolationException $e) {
                // Race conditions probably means that a device has been created between the matching device check
                // and the previous DeviceManager::create. Try to find again the matching device that should now exist
                $device = $this->getMatchingDevice(
                    deviceUuid: $deviceUuid,
                    fingerprint: $fingerprint,
                    deviceDto: $detectedDevice,
                    ckeckIpAndUser: $checkIp,
                    ip: $ip,
                    user: $user,
                    skipDeviceMatchCheck: $skipDeviceMatchCheck,
                );

                if ($device !== null) {
                    return DeviceTransport::set($next(DeviceTransport::propagate($device->uuid)), $device->uuid);
                }

                $this->abort($detectedDevice === null, $detectedDevice?->source ?? 'unknown', $e);
            } catch (UnknownDeviceDetectedException $e) {
                $this->abort($detectedDevice === null, $detectedDevice?->source ?? 'unknown', $e);
            }

            DeviceManager::track($device->uuid);

            return DeviceTransport::set($next(DeviceTransport::propagate($device->uuid)), $device->uuid);
        }

        // Device does not exist and it should not be tracked. Keep in the request the uuid provided or set a new uuid
        $deviceUuid ??= DeviceIdFactory::generate();

        return DeviceTransport::set($next(DeviceTransport::propagate($deviceUuid)), $deviceUuid);
    }

    private function checkCustomDeviceTransportHierarchy(?string $hierarchyParameterString = null): void
    {
        if (! empty($hierarchyParameterString)) {
            $hierarchy = array_filter(
                explode('|', $hierarchyParameterString),
                fn (string $value) => Transport::tryFrom($value) !== null,
            );
            if (! empty($hierarchy)) {
                Config::set('devices.transports.device_id.transport_hierarchy', $hierarchy);
            }
        }
    }

    /**
     * Applies a custom response transport for the device_id transport when a valid, non-default transport string is provided.
     *
     * If `$parameterString` is non-empty, corresponds to a known Transport value, and is not the Request transport,
     * the configuration key `devices.transports.device_id.response_transport` is set to that value.
     *
     * @param string|null $parameterString The transport name to apply (e.g., a Transport enum value); ignored if null, empty, invalid, or equal to the default Request transport.
     */
    private function checkCustomDeviceResponseTransport(?string $parameterString = null): void
    {
        if (
            ! empty($parameterString)
            && Transport::tryFrom($parameterString) !== null
            && $parameterString !== Transport::Request->value
        ) {
            Config::set('devices.transports.device_id.response_transport', $parameterString);
        }
    }

    /**
     * Finds an existing Device that matches the provided identifiers and optional context.
     *
     * If a matching device is found and `$skipDeviceMatchCheck` is false, the device's info
     * will be updated with the given fingerprint and device DTO before being returned.
     *
     * @param StorableId|null $deviceUuid Device UUID to match, if available.
     * @param StorableId|null $fingerprint Fingerprint identifier to match, if available.
     * @param DeviceDto|null $deviceDto Device data transfer object used for matching and updating.
     * @param bool $ckeckIpAndUser When true, include `$ip` and `$user` in the matching criteria.
     * @param string|null $ip Client IP address used when `$ckeckIpAndUser` is true.
     * @param Authenticatable|null $user Authenticated user used when `$ckeckIpAndUser` is true.
     * @param bool $skipDeviceMatchCheck When true, return a found device without updating its stored info.
     * @return Device|null The matching Device instance, or `null` if no match was found.
     */
    private function getMatchingDevice(
        ?StorableId $deviceUuid,
        ?StorableId $fingerprint,
        ?DeviceDto $deviceDto,
        bool $ckeckIpAndUser = false,
        ?string $ip = null,
        ?Authenticatable $user = null,
        bool $skipDeviceMatchCheck = false,
    ): ?Device {
        $device = DeviceManager::matchingDevice(
            deviceUuid: $deviceUuid,
            fingerprint: $fingerprint,
            deviceDto: $deviceDto,
            ip: $ckeckIpAndUser ? $ip : null,
            user: $ckeckIpAndUser ? $user : null,
            skipMatchCheck: $skipDeviceMatchCheck,
        );

        if ($device !== null) {
            if (! $skipDeviceMatchCheck) {
                $device = $device->updateInfo(
                    fingerprint: $fingerprint,
                    data: $deviceDto,
                );
            }

            return $device;
        }

        return null;
    }

    /**
     * @throws UnknownDeviceDetectedException
     * @throws InvalidDeviceDetectedException
     */
    private function isDeviceAllowed(bool $unknown = true, ?string $userAgent = null): void
    {
        if (isset($userAgent) && DeviceManager::isWhitelisted($userAgent)) {
            return;
        }
        if (Config::get('devices.allow_'.($unknown ? 'unknown' : 'bot').'_devices', false) === false) {
            $this->abort($unknown, $userAgent);
        }
    }

    /**
     * @throws InvalidDeviceDetectedException
     * @throws UnknownDeviceDetectedException
     */
    private function abort(bool $unknown = false, ?string $userAgent = null, ?Throwable $e = null): void
    {
        if ($e !== null) {
            Log::error(sprintf('Device exception caught (%s): %s', get_class($e), $e->getMessage()));
        }

        if (! $this->shouldThrow()) {
            $errorCode = config('devices.middlewares.device-tracker.http_error_code', 403);
            if (! array_key_exists($errorCode, Response::$statusTexts)) {
                $errorCode = 403;
            }
            abort($errorCode, sprintf(
                '%s device detected: user-agent %s',
                $unknown ? 'Unknown' : 'Bot',
                $userAgent
            ));
        } else {
            if ($unknown === true) {
                throw UnknownDeviceDetectedException::withUA($userAgent);
            }
            throw InvalidDeviceDetectedException::withUA($userAgent);
        }
    }

    private function shouldThrow(): bool
    {
        return Config::get('devices.middlewares.device-tracker.exception_on_invalid_devices', false);
    }

    private function guessLoggingUser(): ?Authenticatable
    {
        $guesserClass = config('devices.logged_user_guesser');
        if ($guesserClass === null) {
            return null;
        }

        $instance = null;
        try {
            $instance = app($guesserClass);
        } catch (Throwable $e) {
            Log::info($e->getMessage());
        }

        if (! ($instance instanceof LoggedUserGuesser)) {
            try {
                $instance = new $guesserClass;
            } catch (Throwable $e) {
                Log::info($e->getMessage());
            }
        }

        return $instance instanceof LoggedUserGuesser
            ? $instance->guess()
            : null;
    }
}