<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\Processors\MetricProcessor;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final readonly class ProcessMetricsTask
{
    private function __construct(private Aggregation $aggregation, private ?OutputInterface $output = null)
    {
    }

    /**
     * @throws MetricHandlerNotFoundException
     * @throws Throwable
     */
    public function __invoke(): void
    {
        $tw = TimeWindow::forAggregation($this->aggregation);
        $this->output?->writeln(sprintf('Processing window: %s', $tw));

        app(MetricProcessor::class)->process(new Window($tw));
    }

    public static function with(Aggregation $aggregation): self
    {
        if (app()->runningInConsole()) {
            return new self($aggregation, app(OutputInterface::class));
        }

        return new self($aggregation);
    }
}
