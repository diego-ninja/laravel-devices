<?php

namespace Ninja\DeviceTracker;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Contracts\LocationProvider;
use Ninja\DeviceTracker\Middleware\SessionTracker;

class DeviceTrackerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMiddlewares();
        $this->registerRoutes();
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

        $this->registerFacades();
        $this->registerAuthenticationEventHandler();
    }

    private function registerMiddlewares(): void
    {
        $router = $this->app['router'];
        $router->middleware('session', SessionTracker::class);
    }

    private function registerRoutes(): void
    {
        $router = $this->app['router'];
        $router->group(['middleware' => 'auth'], function () use ($router) {
            $router->get('devices', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@list')->name('devices.list');
            $router->get('devices/{id}', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@show')->name('devices.show');
        });
        $router->group(['middleware' => 'auth'], function () use ($router) {
            $router->get('sessions', 'Ninja\DeviceTracker\Http\Controllers\SessionController@list')->name('sessions.list');
            $router->get('sessions/{id}', 'Ninja\DeviceTracker\Http\Controllers\SessionController@show')->name('sessions.show');
            $router->post('sessions/{id}/end', 'Ninja\DeviceTracker\Http\Controllers\SessionController@end')->name('sessions.end');
            $router->post('sessions/{id}/lock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@lock')->name('sessions.lock');
            $router->post('sessions/{id}/unlock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@unlock')->name('sessions.unlock');
        });
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
