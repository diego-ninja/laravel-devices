<?php

namespace Ninja\DeviceTracker\Modules\Observability\Provider;

use Carbon\Laravel\ServiceProvider;
use Event;
use Illuminate\Console\Scheduling\Schedule;
use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Modules\Observability\Console\Commands\ProcessMetricsCommand;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\MetricAggregator;
use Ninja\DeviceTracker\Modules\Observability\MetricCollector;
use Ninja\DeviceTracker\Modules\Observability\MetricProcessor;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Repository\DatabaseMetricAggregationRepository;

final class DeviceMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetricAggregationRepository::class, function () {
            return new DatabaseMetricAggregationRepository();
        });

        $this->app->singleton(MetricAggregator::class, function ($app) {
            return new MetricAggregator();
        });

        $this->app->singleton(MetricProcessor::class, function ($app) {
            return new MetricProcessor(
                $app->make(DatabaseMetricAggregationRepository::class)
            );
        });

        $this->app->singleton(MetricCollector::class, function ($app) {
            return new MetricCollector(
                $app->make(MetricAggregator::class)
            );
        });
    }

    public function boot(): void
    {
        Registry::initialize();
        $this->listen();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessMetricsCommand::class
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('devices:metrics realtime')
                ->everyMinute()
                ->withoutOverlapping();

            $schedule->command('devices:metrics hourly --prune')
                ->hourly()
                ->withoutOverlapping();

            $schedule->command('devices:metrics daily --prune')
                ->daily()
                ->withoutOverlapping();

            $schedule->command('devices:metrics weekly --prune')
                ->weekly()
                ->withoutOverlapping();

            $schedule->command('devices:metrics monthly --prune')
                ->monthly()
                ->withoutOverlapping();
        });
    }

    private function listen(): void
    {
        $collector = $this->app->make(MetricCollector::class);
        Event::listen(DeviceCreatedEvent::class, fn(DeviceCreatedEvent $event) => $collector->handleDeviceCreated($event));
    }
}
