<?php

namespace Ninja\DeviceTracker;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Middleware\SessionTracker;

class DeviceTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();

        $router = $this->app['router'];
        $router->middleware('session', SessionTracker::class);
    }

    public function register(): void
    {
        $config = __DIR__ . '/../config/devices.php';
        $this->mergeConfigFrom(
            path: $config,
            key: 'devices'
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

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/devices.php' => config_path('devices.php')], 'config');

            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations')
            ], 'device-tracker-migrations');
        }
    }
}
