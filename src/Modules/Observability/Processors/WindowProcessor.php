<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
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
        private readonly MetricStorage $storage,
        private readonly StateManager $state
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
        } catch (Throwable $e) {
            $this->state->error($window->aggregation);
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

        $this->state->success($window);
    }

    public function pending(Aggregation $windowType): Collection
    {
        return collect($this->storage->keys($windowType->pattern()))
            ->map(function ($key) {
                return Key::decode($key)->asTimeWindow();
            })
            ->filter(function (TimeWindow $window) use ($windowType) {
                return
                    $window->aggregation === $windowType &&
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
