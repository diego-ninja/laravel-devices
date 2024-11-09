<?php

namespace Ninja\DeviceTracker\Modules\Observability\Console\Commands;

use DateInterval;
use Illuminate\Console\Command;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\MetricManager;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final class ProcessMetricsCommand extends Command
{
    protected $signature = 'devices:metrics
        {window : Window to process (realtime, hourly, daily, weekly, monthly)}
        {--prune : Prune old data}
        {--process-pending : Process pending windows before current window}
        {--force : Force processing of pending windows even if marked as processed}';

    protected $description = 'Process metrics from realtime storage to persistent storage';

    public function __construct(protected readonly WindowProcessor $processor, protected readonly MetricAggregationRepository $repository)
    {
        parent::__construct();
    }
    public function handle(): void
    {
        $window = AggregationWindow::tryFrom($this->argument('window'));

        try {
            $this->processWindow($window);
            $this->pruneIfRequested($window);
            $this->stats($window);
        } catch (Throwable $e) {
            $this->handleError($e, $window);
        }
    }

    /**
     * @throws Throwable
     */
    private function processWindow(AggregationWindow $window): void
    {
        $timeWindow = TimeWindow::forAggregation($window);

        if ($this->option('process-pending')) {
            $this->processPending($window);
        }

        $this->info(sprintf('Processing window: %s', $timeWindow));
        $this->processor->process(new Window($timeWindow));
    }

    private function processPending(AggregationWindow $window): void
    {
        $pendingWindows = $this->processor->pending($window);

        if ($pendingWindows->isEmpty()) {
            $this->info('No pending windows found.');
            return;
        }

        $this->info(sprintf(
            'Found %d pending %s windows to process',
            $pendingWindows->count(),
            $window->value
        ));

        $table = [];
        foreach ($pendingWindows as $pendingWindow) {
            $this->info(sprintf('Processing pending window: %s', $pendingWindow));

            try {
                $this->processor->process(new Window($pendingWindow));

                $table[] = [
                    $pendingWindow->window->value,
                    $pendingWindow->from->toDateTimeString(),
                    $pendingWindow->to->toDateTimeString(),
                    'Success'
                ];
            } catch (Throwable $e) {
                $table[] = [
                    $pendingWindow->window->value,
                    $pendingWindow->from->toDateTimeString(),
                    $pendingWindow->to->toDateTimeString(),
                    'Failed: ' . $e->getMessage()
                ];

                if (!$this->option('force')) {
                    throw $e;
                }
            }
        }

        $this->table(
            ['Window', 'From', 'To', 'Status'],
            $table
        );
    }

    /**
     * @throws \DateMalformedIntervalStringException
     */
    private function pruneIfRequested(AggregationWindow $window): void
    {
        if (!$this->option('prune')) {
            return;
        }

        $retention = config(
            sprintf('devices.observability.aggregation.retention.%s', $window->value),
            '7 days'
        );

        $before = now()->sub(new DateInterval($retention));

        $this->info(sprintf(
            'Pruning data before %s',
            $before->toDateTimeString()
        ));

        $deleted = $this->repository->prune($window, $before);

        $this->info(sprintf(
            'Pruned %d records',
            $deleted
        ));
    }

    private function stats(AggregationWindow $window): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Last Processing', $this->processor->state()->last($window)?->diffForHumans() ?? 'Never'],
                ['Error Count', $this->processor->state()->errors($window)],
                ['Window Type', $window->value],
                ['Retention Period', config(sprintf('devices.observability.aggregation.retention.%s', $window->value), '7 days')],
                ['Pending Windows', $this->processor->pending($window)->count()]
            ]
        );
    }

    private function handleError(Throwable $e, AggregationWindow $window): void
    {
        $this->error(sprintf(
            'Processing failed for %s window. Error: %s',
            $window->value,
            $e->getMessage()
        ));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Window', $window->value],
                ['Error Count', $this->processor->state()->errors($window)],
                ['Last Success', $this->processor->state()->last($window)?->diffForHumans() ?? 'Never'],
                ['Error', $e->getMessage()],
                ['Trace', $e->getTraceAsString()]
            ]
        );

        throw $e;
    }
}
