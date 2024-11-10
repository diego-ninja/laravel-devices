<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Illuminate\Console\OutputStyle;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final readonly class ProcessMetricsTask
{
    private WindowProcessor $processor;

    private function __construct(private Aggregation $aggregation, private ?OutputStyle $output = null)
    {
        $this->processor = app(WindowProcessor::class);
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): void
    {
        try {
            $current = TimeWindow::forAggregation($this->aggregation);

            $this->processor->process(new Window($current));
            $this->output?->info(sprintf('Processing %s window: [%s]', $this->aggregation->value, $current));

            $next = $current->next();
            if ($next) {
                if (!$this->processor->state()->wasSuccess($next)) {
                    $this->output?->info(sprintf('Processing %s window: [%s]', $next->aggregation->value, $next));
                    $this->processor->process(new Window($next));
                } else {
                    $this->output?->writeln(sprintf('Window %s already processed [%s]', $next->aggregation->value, $next));
                }
            }
        } catch (Throwable $e) {
            $this->output?->error(sprintf(
                'Failed to process metrics for %s: %s',
                $this->aggregation->value,
                $e->getMessage()
            ));

            throw $e;
        }
    }

    public static function with(Aggregation $aggregation, ?OutputStyle $output = null): self
    {
        if (app()->runningInConsole()) {
            return new self($aggregation, $output);
        }

        return new self($aggregation);
    }
}
