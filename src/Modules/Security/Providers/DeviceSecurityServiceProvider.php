<?php

namespace Ninja\DeviceTracker\Modules\Security\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Ninja\DeviceTracker\Modules\Security\Console\Commands\DeviceSecurityAssessmentsList;
use Ninja\DeviceTracker\Modules\Security\Console\Commands\DeviceSecurityAssessmentsRun;
use Ninja\DeviceTracker\Modules\Security\Contracts\DeviceSecurityAssessmentsProviderInterface;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityManagerInterface;
use Ninja\DeviceTracker\Modules\Security\Managers\SecurityManager;
use Psr\Log\LoggerInterface;

class DeviceSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (config('devices.modules.security.enabled', false) === true) {
            $this->registerManager();
            // $this->registerEventHandlers();
        }
    }

    public function boot(): void
    {
        if (config('devices.modules.security.enabled', false) === true) {
            $this->registerMiddlewares();
            $this->registerCommands();
            $this->registerAssessments();
        }
    }

    public function provides(): array
    {
        return [
            'device_security_manager',
            SecurityManagerInterface::class,
        ];
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeviceSecurityAssessmentsList::class,
                DeviceSecurityAssessmentsRun::class,
            ]);
        }
    }

    private function registerMiddlewares(): void
    {
        // try {
        //     $router = $this->app->get('router');
        //     $router->aliasMiddleware('device-tracker', DeviceTracker::class);
        // } catch (\Throwable $e) {
        // }
    }

    private function registerManager(): void
    {
        $securityManager = new SecurityManager(app()->make(LoggerInterface::class));
        $this->app->singleton('device_security_manager', fn () => $securityManager);
        $this->app->singleton(SecurityManagerInterface::class, fn () => $securityManager);
    }

    private function registerEventHandlers(): void
    {
        // Event::subscribe(EventSubscriber::class);
    }

    private function registerAssessments(): void
    {
        $providers = app()->getLoadedProviders();
        /** @var SecurityManagerInterface $manager */
        $manager = app()->make(SecurityManagerInterface::class);
        foreach ($providers as $providerClass => $loaded) {
            if (is_subclass_of($providerClass, DeviceSecurityAssessmentsProviderInterface::class)) {
                /** @var DeviceSecurityAssessmentsProviderInterface $provider */
                $provider = app()->getProvider($providerClass);
                $manager->addSecurityAssessments($provider->getDeviceSecurityAssessments());
            }
        }
    }
}
