<?php

namespace Ninja\DeviceTracker;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Middleware\SessionTracker;

class DeviceTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('devices.php')], 'config');

        $this->publishes([
            __DIR__ . '/migrations' => base_path('database/migrations')
        ], 'migrations');

        $router = $this->app['router'];
        $router->middleware('session', SessionTracker::class);
    }

    public function register(): void
    {
        $config = __DIR__ . '/config/config.php';
        $this->mergeConfigFrom(
            $config,
            'devices'
        );
        $this->registerDeviceTracker();
        $this->registerAuthenticationEventHandler();
    }

    private function registerDeviceTracker(): void
    {
        $this->app->bind('deviceTracker', function ($app) {
            return new DeviceTracker($app);
        });
    }

    private function registerAuthenticationEventHandler(): void
    {
        Event::subscribe(AuthenticationHandler::class);
    }
}
