<?php

namespace Ninja\DeviceTracker\Modules\Observability\Provider;

use Carbon\Laravel\ServiceProvider;
use Event;
use Illuminate\Console\Scheduling\Schedule;
use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Events\DeviceDeletedEvent;
use Ninja\DeviceTracker\Events\DeviceHijackedEvent;
use Ninja\DeviceTracker\Events\DeviceVerifiedEvent;
use Ninja\DeviceTracker\Modules\Observability\Collectors\DeviceMetricCollector;
use Ninja\DeviceTracker\Modules\Observability\Console\Commands\ProcessMetricsCommand;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\MetricAggregator;
use Ninja\DeviceTracker\Modules\Observability\MetricMerger;
use Ninja\DeviceTracker\Modules\Observability\MetricManager;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Average;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Counter;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Gauge;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Histogram;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Rate;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\Summary;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\StateStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\RedisMetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\RedisStateStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\MetricProcessor;
use Ninja\DeviceTracker\Modules\Observability\Processors\TypeProcessor;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\Repository\DatabaseMetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\StateManager;

final class DeviceMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetricStorage::class, function ($app) {
            return new RedisMetricStorage(config('devices.metrics.aggregation.prefix'));
        });

        $this->app->singleton(StateStorage::class, function ($app) {
            return new RedisStateStorage(config('devices.metrics.aggregation.prefix'));
        });

        $this->app->singleton(StateManager::class, function ($app) {
            return new StateManager(
                $app->make(StateStorage::class)
            );
        });

        $this->app->singleton(MetricAggregationRepository::class, function () {
            return new DatabaseMetricAggregationRepository();
        });

        $this->app->singleton(MetricMerger::class, function ($app) {
            return new MetricMerger(
                $app->make(MetricAggregationRepository::class)
            );
        });

        $this->app->singleton(MetricProcessor::class, function ($app) {
            return new MetricProcessor(
                $app->make(MetricMerger::class),
                $app->make(MetricStorage::class)
            );
        });

        $this->app->singleton(TypeProcessor::class, function ($app) {
            return new TypeProcessor(
                $app->make(MetricProcessor::class),
                $app->make(MetricStorage::class)
            );
        });

        $this->app->singleton(WindowProcessor::class, function ($app) {
            return new WindowProcessor(
                $app->make(TypeProcessor::class),
                $app->make(MetricMerger::class),
                $app->make(MetricStorage::class),
                $app->make(StateManager::class)
            );
        });

        $this->app->singleton(MetricAggregator::class, function ($app) {
            return new MetricAggregator(
                $app->make(MetricStorage::class)
            );
        });

        $this->app->singleton(MetricManager::class, function ($app) {
            return new MetricManager(
                $app->make(WindowProcessor::class),
                $app->make(MetricStorage::class),
                $app->make(StateManager::class)
            );
        });

        $this->app->singleton(DeviceMetricCollector::class, function ($app) {
            return new DeviceMetricCollector();
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
        $collector = $this->app->make(DeviceMetricCollector::class);

        Event::listen(DeviceCreatedEvent::class, fn(DeviceCreatedEvent $event) => $collector->handleDeviceCreated($event));
        Event::listen(DeviceVerifiedEvent::class, fn(DeviceVerifiedEvent $event) => $collector->handleDeviceVerified($event));
        Event::listen(DeviceHijackedEvent::class, fn(DeviceHijackedEvent $event) => $collector->handleDeviceHijacked($event));
        Event::listen(DeviceDeletedEvent::class, fn(DeviceDeletedEvent $event) => $collector->handleDeviceDeleted($event));
    }
}
