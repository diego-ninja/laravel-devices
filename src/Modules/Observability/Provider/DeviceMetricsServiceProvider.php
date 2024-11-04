<?php

namespace Ninja\DeviceTracker\Modules\Observability\Provider;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Ninja\DeviceTracker\Modules\Observability\Console\Commands\ProcessMetricsCommand;
use Ninja\DeviceTracker\Modules\Observability\Repository\DatabaseMetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Tracking\Aggregation\Aggregator\EventAggregator;
use Ninja\DeviceTracker\Modules\Tracking\Aggregation\Contracts\EventAggregationRepository;
use Ninja\DeviceTracker\Modules\Tracking\Aggregation\Processor\AggregationProcessor;

final class DeviceMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventAggregationRepository::class, function () {
            return new DatabaseMetricAggregationRepository();
        });

        $this->app->singleton(EventAggregator::class, function ($app) {
            return new EventAggregator();
        });

        $this->app->singleton(AggregationProcessor::class, function ($app) {
            return new AggregationProcessor(
                $app->make(EventAggregationRepository::class)
            );
        });
    }

    public function boot(): void
    {
        // Registrar comando
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
}
