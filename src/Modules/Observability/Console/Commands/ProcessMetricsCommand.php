<?php

namespace Ninja\DeviceTracker\Modules\Observability\Console\Commands;

use DateInterval;
use Illuminate\Console\Command;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\MetricManager;
use Throwable;

final class ProcessMetricsCommand extends Command
{
    protected $signature = 'devices:metrics
        {window : Window to process (realtime, hourly, daily, weekly, monthly)}
        {--prune : Prune old data}';

    protected $description = 'Process metrics from realtime storage to persistent storage';

    public function __construct(protected readonly MetricManager $manager, protected readonly MetricAggregationRepository $repository)
    {
        parent::__construct();
    }
    public function handle(): void
    {
        $window = AggregationWindow::tryFrom($this->argument('window'));

        try {
            $this->manager->process($window);

            $this->info(sprintf(
                'Last processing: %s',
                $this->manager->last($window)?->diffForHumans() ?? 'never'
            ));

            if ($this->option('prune')) {
                $retention = config(sprintf("devices.metrics.retention.%s", $window->value), '7 days');
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

            $this->stats($window);
            $this->manager->reset($window);
        } catch (Throwable $e) {
            $this->error(sprintf(
                'Processing failed. Error count: %d',
                $this->manager->errors($window)
            ));
        }
    }

    private function stats(AggregationWindow $window): void
    {
        $lastProcessing = $this->manager->last($window);
        $errorCount = $this->manager->errors($window);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Last Processing', $lastProcessing ? $lastProcessing->diffForHumans() : 'Never'],
                ['Error Count', $errorCount],
                ['Window', $window->value],
            ]
        );
    }
}
