<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Illuminate\Console\OutputStyle;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\Processors\WindowProcessor;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final readonly class ProcessMetricsTask
{
    private WindowProcessor $processor;

    private function __construct(private TimeWindow $window, private ?OutputStyle $output = null)
    {
        $this->processor = app(WindowProcessor::class);
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): void
    {
        try {
            $this->processor->process(new Window($this->window));
            $this->output?->info(sprintf('Processing %s window: [%s]', $this->window->aggregation->value, $this->window));

            $next = $this->window->next();
            while ($next !== null) {
                if (!$this->processor->state()->wasSuccess($next)) {
                    $this->output?->info(sprintf('Processing %s window: [%s]', $next->aggregation->value, $next));
                    $this->processor->process(new Window($next));
                } else {
                    $this->output?->writeln(sprintf('Window %s already processed [%s]', $next->aggregation->value, $next));
                }
                $next = $next->next();
            }
        } catch (Throwable $e) {
            $this->output?->error(sprintf(
                'Failed to process metrics for %s: %s',
                $this->window->aggregation->value,
                $e->getMessage()
            ));

            throw $e;
        }
    }

    public static function with(TimeWindow $window, ?OutputStyle $output = null): self
    {
        if (app()->runningInConsole()) {
            return new self($window, $output);
        }

        return new self($window);
    }
}
