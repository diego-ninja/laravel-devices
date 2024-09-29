<?php

namespace Ninja\DeviceTracker;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Ninja\DeviceTracker\Events\Google2FASuccess;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Facades\SessionManager;
use Ninja\DeviceTracker\Models\Session;

final readonly class AuthenticationHandler
{
    public function onLogin(Login $event): void
    {
        DeviceManager::addUserDevice(request());

        if (SessionManager::forgot()) {
            SessionManager::start();
        } else {
            SessionManager::renew();
        }
    }

    public function onLogout(Logout $event): void
    {
        SessionManager::end(
            forgetSession: true,
            user: $event->user,
        );
    }

    public function onGoogle2FASuccess(Google2FASuccess $event): void
    {
        $user = $event->user;
        $user->currentSession()->device->verify();
        $user->currentSession()->unlock();

        //Session::current()->device->verify();
        //Session::current()->unlock();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('Illuminate\Auth\Events\Login', 'Ninja\DeviceTracker\AuthenticationHandler@onLogin');
        $events->listen('Illuminate\Auth\Events\Logout', 'Ninja\DeviceTracker\AuthenticationHandler@onLogout');
        $events->listen('Ninja\DeviceTracker\Events\Google2FASuccess', 'Ninja\DeviceTracker\AuthenticationHandler@onGoogle2FASuccess');
    }
}
