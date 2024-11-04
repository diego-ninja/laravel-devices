<?php

namespace Ninja\DeviceTracker\Modules\Observability\Console\Commands;

use DateInterval;
use Illuminate\Console\Command;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Tracking\Aggregation\Contracts\EventAggregationRepository;
use Ninja\DeviceTracker\Modules\Tracking\Aggregation\Processor\AggregationProcessor;
use Throwable;

final class ProcessMetricsCommand extends Command
{
    protected $signature = 'devices:metrics 
        {window : Window to process (realtime, hourly, daily, weekly, monthly)}
        {--prune : Prune old data}';

    public function __construct(protected readonly AggregationProcessor $processor, protected readonly EventAggregationRepository $repository)
    {
        parent::__construct();
    }
    public function handle(AggregationProcessor $processor): void
    {
        $window = AggregationWindow::tryFrom($this->argument('window'));

        try {
            $processor->window($window);

            $this->info(sprintf(
                'Last processing: %s',
                $processor->time($window)?->diffForHumans() ?? 'never'
            ));

            if ($this->option('prune')) {
                $retention = config(sprintf("devices.events.aggregation.retention.%s", $window->name), '7 days');
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
                $processor->errorCount($window)
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
