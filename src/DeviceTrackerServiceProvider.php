<?php

namespace Ninja\DeviceTracker;

use Config;
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

        if (Config::get('devices.load_routes')) {
            $this->registerRoutes();
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
        $router->group([
            'as' => 'device::',
            'prefix' => 'api/devices',
            'middleware' => Config::get('devices.auth_middleware')
        ], function () use ($router) {
            $router->get('/', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@list')->name('list');
            $router->get('/{id}', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@show')->name('show');
            $router->get('/{id}/verify', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@verify')->name('verify');
            $router->get('/{id}/hijack', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@hijack')->name('hijack');
            $router->get('/{id}/forget', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@forget')->name('forget');
        });

        $router->group([
            'as' => 'session::',
            'prefix' => 'api/sessions',
            'middleware' => Config::get('devices.auth_middleware')
        ], function () use ($router) {
            $router->get('/', 'Ninja\DeviceTracker\Http\Controllers\SessionController@list')->name('list');
            $router->get('/{id}', 'Ninja\DeviceTracker\Http\Controllers\SessionController@show')->name('show');
            $router->post('/{id}/end', 'Ninja\DeviceTracker\Http\Controllers\SessionController@end')->name('end');
            $router->post('/{id}/lock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@lock')->name('lock');
            $router->post('/{id}/unlock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@unlock')->name('unlock');
            $router->post('/{id}/refresh', 'Ninja\DeviceTracker\Http\Controllers\SessionController@refresh')->name('refresh');
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
