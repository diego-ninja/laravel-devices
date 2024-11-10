<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Illuminate\Console\Concerns\InteractsWithIO;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final class ProcessMetricsTask
{
    use InteractsWithIO;

    private readonly WindowProcessor $processor;

    private function __construct(private readonly Aggregation $aggregation)
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
            $this->output?->writeln(sprintf('Processing %s window: [%s]', $this->aggregation->value, $current));

            $next = $current->next();
            if ($next) {
                if (!$this->processor->state()->wasSuccess($next)) {
                    $this->output?->writeln(sprintf('Processing %s window: [%s]', $next->aggregation->value, $next));
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

    public static function with(Aggregation $aggregation): self
    {
        return new self($aggregation);
    }
}
