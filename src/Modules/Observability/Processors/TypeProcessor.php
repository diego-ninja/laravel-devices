<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processor;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Metric;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Type;
use Throwable;

final readonly class TypeProcessor implements Processor
{
    private Collection $keys;

    public function __construct(
        private MetricProcessor $metricProcessor,
        private MetricStorage $storage
    ) {
        $this->keys = collect();
    }

    /**
     * @throws MetricHandlerNotFoundException
     * @throws Throwable
     */
    public function process(Processable $item): void
    {
        if (!$item instanceof Type) {
            throw new InvalidArgumentException('Invalid processable type');
        }

        $keys = $this->storage->keys($this->pattern($item));

        foreach ($keys as $key) {
            $metric = new Metric(
                Key::decode($key),
                $item->window()
            );

            $this->metricProcessor->process($metric);
            $this->keys->push($key);
        }
    }

    private function pattern(Type $item): string
    {
        return sprintf(
            '%s:*:%s:%s:%d:*',
            config('devices.observability.prefix'),
            $item->type()->value,
            $item->window()->window->value,
            $item->window()->slot
        );
    }

    public function keys(): Collection
    {
        return $this->keys;
    }
}
