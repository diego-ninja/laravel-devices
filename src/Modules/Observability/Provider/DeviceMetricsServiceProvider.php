<?php

namespace Ninja\DeviceTracker\Modules\Observability\Provider;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Octane\Facades\Octane;
use Ninja\DeviceTracker\Modules\Observability\Collectors\DeviceMetricCollector;
use Ninja\DeviceTracker\Modules\Observability\Console\Commands\ProcessMetricsCommand;
use Ninja\DeviceTracker\Modules\Observability\Console\Commands\PruneMetricsCommand;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\MetricAggregator;
use Ninja\DeviceTracker\Modules\Observability\MetricManager;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\StateStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\RedisMetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\RedisStateStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\MetricProcessor;
use Ninja\DeviceTracker\Modules\Observability\Processors\TypeProcessor;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\Repository\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Repository\DatabaseMetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\StateManager;
use Ninja\DeviceTracker\Modules\Observability\Tasks\ProcessMetricsTask;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

final class DeviceMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetricStorage::class, function ($app) {
            return new RedisMetricStorage(
                prefix: config('devices.observability.prefix'),
                connection: config('devices.observability.storage.connection')
            );
        });

        $this->app->singleton(StateStorage::class, function ($app) {
            return new RedisStateStorage(
                prefix: config('devices.observability.prefix'),
                connection: config('devices.observability.state.connection')
            );
        });

        $this->app->singleton(StateManager::class, function ($app) {
            return new StateManager(
                $app->make(StateStorage::class)
            );
        });

        $this->app->singleton(MetricAggregationRepository::class, function () {
            return new DatabaseMetricAggregationRepository();
        });

        $this->app->singleton(MetricProcessor::class, function ($app) {
            return new MetricProcessor(
                $app->make(MetricStorage::class),
                $app->make(MetricAggregationRepository::class)
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

        if (config('devices.observability.enabled') && config('devices.load_routes')) {
            $this->loadRoutesFrom(__DIR__ . '/../../../../routes/metrics.php');
        }
    }

    public function boot(): void
    {
        $this->registerMetrics();
        $this->listen();

        if (config('devices.observability.processing.driver') === 'scheduler') {
            $this->schedule();
        } else {
            $this->tick();
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessMetricsCommand::class,
                PruneMetricsCommand::class
            ]);
        }
    }

    private function schedule(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('devices:metrics:process realtime --process-pending')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }

    private function tick(): void
    {
        Octane::tick('realtime', function () {
            ProcessMetricsTask::with(TimeWindow::forAggregation(Aggregation::Realtime))();
        })->seconds(Aggregation::Realtime->seconds());
    }

    private function listen(): void
    {
        $collectors = config('devices.observability.metrics.collectors', []);

        foreach ($collectors as $collector) {
            $this->app->make($collector)->listen();
        }
    }

    private function registerMetrics(): void
    {
        $providers = config('devices.observability.metrics.providers', []);
        foreach ($providers as $provider) {
            $this->app->make($provider)->register();
        }

        Registry::initialize();
    }
}
