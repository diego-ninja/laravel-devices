<?php

namespace Ninja\DeviceTracker;

use Illuminate\Events\Dispatcher;

final readonly class AuthenticationHandler
{
    public function onLogin($event): void
    {
        if (DeviceTrackerFacade::forgotSession()) {
            DeviceTrackerFacade::startSession();
        } else {
            DeviceTrackerFacade::renewSession();
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('Illuminate\Auth\Events\Login', 'Ninja\DeviceTracker\AuthenticationHandler@onLogin');
    }
}
