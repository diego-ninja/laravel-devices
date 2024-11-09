<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\MetricMerger;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processor;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Type;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\StateManager;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final class WindowProcessor implements Processor
{
    private Collection $keys;

    public function __construct(
        private readonly TypeProcessor $typeProcessor,
        private readonly MetricMerger $merger,
        private readonly MetricStorage $storage,
        private readonly StateManager $state,
        private readonly bool $processPending = false
    ) {
        $this->keys = collect();
    }

    public function process(Processable $item): void
    {
        if (!$item instanceof Window) {
            throw new InvalidArgumentException('Invalid processable type');
        }

        try {
            $window = $item->window();

            $this->processWindow($window);
            if ($this->processPending) {
                $this->processPending($window->window);
            }
        } catch (Throwable $e) {
            $this->state->error($window->window);
            throw $e;
        }
    }

    public function keys(): Collection
    {
        return $this->keys;
    }

    public function state(): StateManager
    {
        return $this->state;
    }

    /**
     * @throws MetricHandlerNotFoundException
     * @throws Throwable
     */
    private function processWindow(TimeWindow $window): void
    {
        foreach (MetricType::all() as $type) {
            $type = new Type($type, $window);
            $this->typeProcessor->process($type);
        }

        if ($window->window !== AggregationWindow::Realtime) {
            $this->merger->merge($window);
        }

        $this->state->success($window);
    }

    private function processPending(AggregationWindow $windowType): void
    {
        $this->pending($windowType)
            ->sortBy(fn(TimeWindow $w) => $w->from->timestamp)
            ->each(function (TimeWindow $window) {
                try {
                    $this->processWindow($window);
                    $this->storage->delete($window);

                    Log::info('Successfully processed pending window', [
                        'window' => $window->window->value,
                        'from' => $window->from->toDateTimeString(),
                        'to' => $window->to->toDateTimeString()
                    ]);
                } catch (Throwable $e) {
                    Log::error('Failed to process pending window', [
                        'window' => $window->window->value,
                        'from' => $window->from->toDateTimeString(),
                        'to' => $window->to->toDateTimeString(),
                        'error' => $e->getMessage()
                    ]);
                    return;
                }
            });
    }

    public function pending(AggregationWindow $windowType): Collection
    {
        return collect($this->storage->keys($windowType->pattern()))
            ->map(function ($key) {
                return Key::decode($key)->asTimeWindow();
            })
            ->filter(function (TimeWindow $window) use ($windowType) {
                return
                    $window->window === $windowType &&
                    $window->from->lt(now()) &&
                    $window->slot < $windowType->timeslot(now()) &&
                    !$this->processed($window);
            })
            ->unique(fn(TimeWindow $w) => $w->slot);
    }

    private function processed(TimeWindow $window): bool
    {
        return $this->state->wasSuccess($window);
    }

}
