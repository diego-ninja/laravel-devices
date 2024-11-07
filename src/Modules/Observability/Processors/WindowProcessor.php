<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\MetricMerger;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processor;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Type;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Window;
use Ninja\DeviceTracker\Modules\Observability\StateManager;
use Throwable;

final class WindowProcessor implements Processor
{
    private Collection $keys;

    public function __construct(
        private readonly TypeProcessor $typeProcessor,
        private readonly MetricMerger $merger,
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

            foreach (MetricType::all() as $type) {
                $type = new Type($type, $window);
                $this->typeProcessor->process($type);
                $this->keys = $this->keys->merge($this->typeProcessor->keys());
            }

            if ($window !== AggregationWindow::Realtime) {
                $this->merger->merge($window);
            }

            $this->state->success($window);
        } catch (Throwable $e) {
            $this->state->error($window);
            throw $e;
        }
    }

    public function keys(): Collection
    {
        return $this->keys;
    }
}
