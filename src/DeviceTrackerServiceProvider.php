<?php

namespace Ninja\DeviceTracker;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Contracts\CodeGenerator;
use Ninja\DeviceTracker\Contracts\DeviceDetector;
use Ninja\DeviceTracker\Generators\Google2FACodeGenerator;
use Ninja\DeviceTracker\Http\Middleware\DeviceTracker;
use Ninja\DeviceTracker\Http\Middleware\FingerprintTracker;
use Ninja\DeviceTracker\Http\Middleware\SessionTracker;
use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\FallbackLocationProvider;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Support\Constants;

class DeviceTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMiddlewares();

        $this->loadViewsFrom(resource_path("views/vendor/laravel-devices"), 'laravel-devices');

        $this->app->resolving(EncryptCookies::class, function (EncryptCookies $encrypter) {
            $encrypter->disableFor(config('devices.client_fingerprint_key'));
        });

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

        $this->registerLocationProviders();

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

    private function registerLocationProviders(): void
    {
        $providers = Config::get('devices.location_providers');
        if (count($providers) === 1) {
            $this->app->singleton(LocationProvider::class, function () use ($providers) {
                return $this->app->make($providers[0]);
            });
        }

        $this->app->singleton(LocationProvider::class, function () use ($providers) {
            $fallbackProvider = new FallbackLocationProvider();
            foreach ($providers as $provider) {
                $fallbackProvider->addProvider($this->app->make($provider));
            }

            return $fallbackProvider;
        });
    }

    private function registerMiddlewares(): void
    {
        $router = $this->app['router'];
        $router->middleware('device-tracker', DeviceTracker::class);
        $router->middleware('session-tracker', SessionTracker::class);

        if (Config::get('devices.fingerprinting_enabled')) {
            $router->middleware('fingerprint-tracker', FingerprintTracker::class);
        }
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
                __DIR__ . '/../resources/views' => resource_path("views/vendor/laravel-devices")], 'views');

            $this->publishes([
                __DIR__ . '/../config/devices.php' => config_path('devices.php')], 'config');

            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations')
            ], 'device-tracker-migrations');
        }
    }
}
