<?php

namespace Ninja\DeviceTracker;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Console\Commands\CacheInvalidateCommand;
use Ninja\DeviceTracker\Console\Commands\CacheWarmCommand;
use Ninja\DeviceTracker\Console\Commands\CleanupDevicesCommand;
use Ninja\DeviceTracker\Console\Commands\CleanupSessionsCommand;
use Ninja\DeviceTracker\Console\Commands\DeviceInspectCommand;
use Ninja\DeviceTracker\Console\Commands\DeviceStatusCommand;
use Ninja\DeviceTracker\Contracts\CodeGenerator;
use Ninja\DeviceTracker\Generators\Google2FACodeGenerator;
use Ninja\DeviceTracker\Http\Middleware\DeviceTracker;
use Ninja\DeviceTracker\Http\Middleware\SessionTracker;
use Ninja\DeviceTracker\Modules\Detection\Contracts\DeviceDetector;
use Ninja\DeviceTracker\Modules\Detection\Device\UserAgentDeviceDetector;
use Ninja\DeviceTracker\Modules\Fingerprinting\Http\Middleware\FingerprintTracker;
use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\FallbackLocationProvider;
use Ninja\DeviceTracker\Modules\Tracking\Http\Middleware\EventTracker;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Support\Constants;

class DeviceTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMiddlewares();
        $this->registerCommands();

        $this->loadViewsFrom(resource_path('views/vendor/laravel-devices'), 'laravel-devices');

        $this->app->resolving(EncryptCookies::class, function (EncryptCookies $encrypter) {
            $encrypter->disableFor(config('devices.client_fingerprint_key'));
        });

        if (config('devices.load_routes') === true) {
            $this->loadRoutesFrom(__DIR__.'/../routes/devices.php');
        }
    }

    public function register(): void
    {
        $config = __DIR__.'/../config/devices.php';
        $this->mergeConfigFrom(
            path: $config,
            key: 'devices'
        );

        $this->registerLocationProviders();

        $this->app->singleton(DeviceDetector::class, function () {
            return new UserAgentDeviceDetector;
        });

        $this->app->singleton(CodeGenerator::class, function () {
            return new Google2FACodeGenerator(app(Google2FA::class));
        });

        $this->app->singleton(Google2FA::class, function () {
            $google2fa = new Google2FA;
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
            $fallbackProvider = new FallbackLocationProvider;
            foreach ($providers as $provider) {
                $fallbackProvider->addProvider($this->app->make($provider));
            }

            return $fallbackProvider;
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheWarmCommand::class,
                CacheInvalidateCommand::class,
                CleanupSessionsCommand::class,
                CleanupDevicesCommand::class,
                DeviceStatusCommand::class,
                DeviceInspectCommand::class,
            ]);
        }
    }

    private function registerMiddlewares(): void
    {
        try {
            $router = $this->app->get('router');
            $router->aliasMiddleware('device-tracker', DeviceTracker::class);
            $router->aliasMiddleware('session-tracker', SessionTracker::class);

            if (config('devices.fingerprinting_enabled') === true) {
                $router->aliasMiddleware('fingerprint-tracker', FingerprintTracker::class);
            }

            if (config('devices.event_tracking_enabled') === true) {
                $router->aliasMiddleware('event-tracker', EventTracker::class);
            }
        } catch (\Throwable $e) {
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
        Event::subscribe(EventSubscriber::class);
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/devices.php' => config_path('devices.php'),
            ], 'laravel-devices-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/laravel-devices'),
            ], 'laravel-devices-views');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'laravel-devices-migrations');
        }
    }
}
