<?php

namespace Ninja\DeviceTracker;

use Config;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Events\DeviceTrackedEvent;
use Ninja\DeviceTracker\Events\Google2FASuccess;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Facades\SessionManager;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;
use Ninja\DeviceTracker\Modules\Tracking\Models\Event;

final readonly class AuthenticationHandler
{
    public function onLogin(Login $event): void
    {
        if (!DeviceManager::tracked()) {
            DeviceManager::track();
            DeviceManager::create();
        }

        DeviceManager::attach();
        SessionManager::refresh($event->user);

        Event::log(
            type: EventType::Login,
            session: $event->user->sessions()->current(),
            metadata: new Metadata([
                "url" => request()->url(),
                "user_agent" => request()->userAgent(),
                "route" => request()->route()?->getName(),
                "method" => request()->method(),
            ])
        );
    }

    public function onLogout(Logout $event): void
    {
        Event::log(
            type: EventType::Logout,
            session: Session::current(),
            metadata: new Metadata([
                "url" => request()->url(),
                "user_agent" => request()->userAgent(),
                "route" => request()->route()?->getName(),
                "method" => request()->method(),
            ])
        );

        Session::current()?->end(
            forgetSession: true,
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
        if (!config('devices.track_guest_sessions')) {
            return;
        }

        if (!Device::exists($event->deviceUuid)) {
            return;
        }

        if (auth(Config::get('devices.auth_guard'))->user()) {
            DeviceManager::attach($event->deviceUuid);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('Illuminate\Auth\Events\Login', 'Ninja\DeviceTracker\AuthenticationHandler@onLogin');
        $events->listen('Illuminate\Auth\Events\Logout', 'Ninja\DeviceTracker\AuthenticationHandler@onLogout');
        $events->listen('Ninja\DeviceTracker\Events\Google2FASuccess', 'Ninja\DeviceTracker\AuthenticationHandler@onGoogle2FASuccess');
        $events->listen('Ninja\DeviceTracker\Events\DeviceTrackedEvent', 'Ninja\DeviceTracker\AuthenticationHandler@onDeviceTracked');
    }
}
