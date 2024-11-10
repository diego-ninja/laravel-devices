<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Log;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final readonly class MetricManager
{
    public function __construct(
        private WindowProcessor $windowProcessor,
        private MetricStorage $storage,
        private StateManager $stateManager
    ) {
    }

    public function process(Aggregation $window): void
    {
        if (!$this->enabled($window)) {
            Log::warning('Attempted to process disabled window', [
                'window' => $window->value
            ]);
            return;
        }

        try {
            $timeWindow = TimeWindow::forAggregation($window);
            $processable = new Window($timeWindow);

            $this->windowProcessor->process($processable);

            $this->cleanup($this->windowProcessor->keys());

            Log::info('Successfully processed metrics window', [
                'window' => $window->value,
                'timeWindow' => $timeWindow->array(),
                'processed_keys' => $this->windowProcessor->keys()->count()
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, $window);
            throw $e;
        }
    }

    public function prune(Aggregation $window): int
    {
        if (!$this->enabled($window)) {
            return 0;
        }

        try {
            $before = now()->sub($window->retention());

            $prunedCount = $this->storage->prune($window, $before);

            Log::info('Pruned old metrics', [
                'window' => $window->value,
                'before' => $before->toDateTimeString(),
                'count' => $prunedCount
            ]);

            return $prunedCount;
        } catch (Throwable $e) {
            Log::error('Failed to prune metrics', [
                'window' => $window->value,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function status(): array
    {
        return [
            'enabled_types' => $this->types()
                ->map(fn(MetricType $type) => $type->value)
                ->all(),
            'windows' => $this->windows()
                ->mapWithKeys(fn(Aggregation $window) => [
                    $window->value => [
                        'last_processing' => $this->last($window)?->toDateTimeString(),
                        'error_count' => $this->errors($window),
                        'interval_seconds' => $window->seconds(),
                        'retention_period' => $window->retention()->days
                    ]
                ])->all(),
            'metrics_count' => $this->count(),
            'system_health' => $this->health()
        ];
    }

    public function last(Aggregation $window): ?Carbon
    {
        return $this->stateManager->last($window);
    }

    public function errors(Aggregation $window): int
    {
        return $this->stateManager->errors($window);
    }

    public function reset(Aggregation $window): void
    {
        $this->stateManager->reset($window);
    }

    private function cleanup(Collection $processedKeys): void
    {
        if ($processedKeys->isNotEmpty()) {
            $this->storage->delete($processedKeys->all());
        }
    }

    private function types(): Collection
    {
        return collect(MetricType::cases())
            ->filter(fn(MetricType $type) => HandlerFactory::handlers()->has($type));
    }


    private function windows(): Collection
    {
        return collect(config('devices.observability.aggregation.windows', [
            Aggregation::Realtime,
            Aggregation::Hourly
        ]));
    }

    private function enabled(Aggregation $window): bool
    {
        return $this->windows()->contains($window);
    }

    private function count(): array
    {
        $counts = [];
        foreach ($this->windows() as $window) {
            $counts[$window->value] = $this->storage->count($window);
        }
        return $counts;
    }

    private function health(): array
    {
        return [
            'storage' => $this->storage->health(),
            'processing' => [
                'active_windows' => $this->windows()->count(),
                'errors_last_hour' => $this->windows()
                    ->sum(fn($window) => $this->errors($window)),
                'processing_status' => $this->determineProcessingStatus()
            ]
        ];
    }

    private function determineProcessingStatus(): string
    {
        $totalErrors = $this->windows()
            ->sum(fn($window) => $this->errors($window));

        if ($totalErrors === 0) {
            return 'healthy';
        }

        return $totalErrors > 10 ? 'degraded' : 'warning';
    }

    private function handleError(Throwable $e, Aggregation $window): void
    {
        Log::error('Failed to process metrics', [
            'window' => $window->value,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
