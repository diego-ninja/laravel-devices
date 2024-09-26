<?php

namespace Ninja\DeviceTracker;

use Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Contracts\CodeGenerator;
use Ninja\DeviceTracker\Contracts\DeviceDetector;
use Ninja\DeviceTracker\Contracts\LocationProvider;
use Ninja\DeviceTracker\Generators\Google2FACodeGenerator;
use Ninja\DeviceTracker\Http\Middleware\SessionTracker;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Support\Constants;

class DeviceTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMiddlewares();

        if (Config::get('devices.load_routes')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/devices.php');
        }
    }

    public function register(): void
    {
        $config = __DIR__ . '/../config/devices.php';
        $this->mergeConfigFrom(
            path: $config,
            key: 'devices'
        );

        $this->app->singleton(LocationProvider::class, function () {
            return new IpinfoLocationProvider();
        });

        $this->app->singleton(DeviceDetector::class, function () {
            return new UserAgentDeviceDetector();
        });

        $this->app->singleton(CodeGenerator::class, function () {
            return new Google2FACodeGenerator(app(Google2FA::class));
        });

        $this->app->singleton(Google2FA::class, function () {
            $google2fa = new Google2FA();
            $google2fa->setAlgorithm(Constants::SHA512);
            $google2fa->setWindow(Config::get('devices.google_2fa_window', 1));

            return $google2fa;
        });

        $this->registerFacades();
        $this->registerAuthenticationEventHandler();
    }

    private function registerMiddlewares(): void
    {
        $router = $this->app['router'];
        $router->middleware('session-tracker', SessionTracker::class);
    }

    private function registerFacades(): void
    {
        $this->app->bind('device_manager', function ($app) {
            return new DeviceManager($app);
        });

        $this->app->bind('session_manager', function ($app) {
            return new SessionManager($app);
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
