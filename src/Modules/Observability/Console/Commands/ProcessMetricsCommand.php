<?php

namespace Ninja\DeviceTracker\Modules\Observability\Console\Commands;

use DateInterval;
use Illuminate\Console\Command;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\MetricProcessor;
use Throwable;

final class ProcessMetricsCommand extends Command
{
    protected $signature = 'devices:metrics 
        {window : Window to process (realtime, hourly, daily, weekly, monthly)}
        {--prune : Prune old data}';

    public function __construct(protected readonly MetricProcessor $processor, protected readonly MetricAggregationRepository $repository)
    {
        parent::__construct();
    }
    public function handle(): void
    {
        $window = AggregationWindow::tryFrom($this->argument('window'));

        try {
            $this->processor->window($window);

            $this->info(sprintf(
                'Last processing: %s',
                $this->processor->time($window)?->diffForHumans() ?? 'never'
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
        } catch (Throwable $e) {
            $this->error(sprintf(
                'Processing failed. Error count: %d',
                $this->processor->errorCount($window)
            ));
        }
    }

    private function stats(AggregationWindow $window): void
    {
        $lastProcessing = $this->processor->time($window);
        $errorCount = $this->processor->errorCount($window);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Last Processing', $lastProcessing ? $lastProcessing->diffForHumans() : 'Never'],
                ['Error Count', $errorCount],
                ['Window', $window],
            ]
        );
    }
}
