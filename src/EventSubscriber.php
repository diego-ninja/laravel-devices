<?php

namespace Ninja\DeviceTracker;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Events\Google2FASuccess;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Facades\SessionManager;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

final readonly class EventSubscriber
{
    /**
     * @throws DeviceNotFoundException
     */
    public function onLogin(Login $event): void
    {
        try {
            if (! DeviceManager::tracked()) {
                DeviceManager::track();
                $device = DeviceManager::create();
                if ($device === null) {
                    throw new DeviceNotFoundException('Failed to create device during login');
                }
            }

            if (! DeviceManager::attach()) {
                throw new DeviceNotFoundException('Failed to attach device during login');
            }

            SessionManager::refresh($event->user);
        } catch (DeviceNotFoundException $e) {
            Log::error('Login failed due to device error', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->getAuthIdentifier(),
            ]);

            throw $e;
        }
    }

    public function onLogout(Logout $event): void
    {
        Session::current()?->end(
            user: $event->user,
        );
    }

    public function onGoogle2FASuccess(Google2FASuccess $event): void
    {
        $user = $event->user;
        $user->session()->device->verify();
        $user->session()->unlock();
    }

    public function onDeviceTracked(DeviceTrackedEvent $event): void
    {
        if (config('devices.track_guest_sessions') === false) {
            return;
        }

        if (! Device::exists($event->deviceUuid)) {
            return;
        }

        if (user() !== null) {
            DeviceManager::attach($event->deviceUuid);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('Illuminate\Auth\Events\Login', [self::class, 'onLogin']);
        $events->listen('Illuminate\Auth\Events\Logout', [self::class, 'onLogout']);
        $events->listen('Ninja\DeviceTracker\Events\Google2FASuccess', [self::class, 'onGoogle2FASuccess']);
        $events->listen('Ninja\DeviceTracker\Events\DeviceTrackedEvent', [self::class, 'onDeviceTracked']);
    }
}
