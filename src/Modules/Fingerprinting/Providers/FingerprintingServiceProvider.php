<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Providers;

use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Modules\Fingerprinting\Http\Middleware\InjectFaviconTracking;
use Ninja\DeviceTracker\Modules\Fingerprinting\Services\FaviconTrackingService;

class FingerprintingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FaviconTrackingService::class);
        if ($this->app->runningWithOctane()) {
            $this->configure();
        }
    }

    public function boot(): void
    {
        if (\Config::get('devices.fingerprinting_enabled')) {
            $this->app["router"]->aliasMiddleware('favtrack', InjectFaviconTracking::class);
            $this->app["router"]->pushMiddlewareToGroup('web', InjectFaviconTracking::class);
        }
    }
}
