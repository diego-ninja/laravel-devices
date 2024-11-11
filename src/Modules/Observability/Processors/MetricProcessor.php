<?php

namespace Ninja\DeviceTracker\Modules\Observability\Processors;

use InvalidArgumentException;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processable;
use Ninja\DeviceTracker\Modules\Observability\Processors\Contracts\Processor;
use Ninja\DeviceTracker\Modules\Observability\Processors\Items\Metric;
use Ninja\DeviceTracker\Modules\Observability\Repository\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Repository\Dto\Metric as MetricDto;
use Throwable;

final readonly class MetricProcessor implements Processor
{
    public function __construct(
        private MetricStorage $storage,
        private MetricAggregationRepository $repository
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

        $value = $this->storage->value($item->key());

        if (empty($value)) {
            return;
        }

        $metric = new MetricDto(
            name: $item->key()->name,
            type: $item->key()->type,
            value: HandlerFactory::compute($item->key()->type, $value),
            timestamp: $item->window()->from,
            dimensions: $item->key()->dimensions,
            aggregation: $item->window()->aggregation
        );

        $this->repository->store($metric);
    }
}
