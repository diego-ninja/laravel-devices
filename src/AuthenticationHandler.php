<?php

namespace Ninja\DeviceTracker;

use Illuminate\Events\Dispatcher;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Facades\SessionManager;

final readonly class AuthenticationHandler
{
    public function onLogin($event): void
    {
        DeviceManager::addUserDevice(request()->userAgent());

        if (SessionManager::forgot()) {
            SessionManager::start();
        } else {
            SessionManager::renew();
        }
    }

    public function onLogout($event): void
    {
        SessionManager::end(forgetSession: true);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('Illuminate\Auth\Events\Login', 'Ninja\DeviceTracker\AuthenticationHandler@onLogin');
        $events->listen('Illuminate\Auth\Events\Logout', 'Ninja\DeviceTracker\AuthenticationHandler@onLogout');
    }
}
