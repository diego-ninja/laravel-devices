<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors;

use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\MetricMerger;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processor;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Metric;
use Throwable;

final readonly class MetricProcessor implements Processor
{
    public function __construct(
        private MetricMerger $merger,
        private MetricStorage $storage
    ) {
    }

    /**
     * @throws MetricHandlerNotFoundException
     * @throws Throwable
     */
    public function process(Processable $item): void
    {
        if (!$item instanceof Metric) {
            throw new InvalidArgumentException('Invalid processable type');
        }

        $metadata = Key::decode($item->key());
        $value = $this->storage->value($item->key(), $item->type());

        if (empty($value)) {
            return;
        }

        $this->merger->store(
            name: $metadata->name,
            type: $item->type(),
            value: HandlerFactory::compute($item->type(), $value),
            dimensions: $metadata->dimensions,
            timeWindow: $item->window()
        );
    }
}
