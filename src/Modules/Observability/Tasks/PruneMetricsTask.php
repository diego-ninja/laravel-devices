<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Illuminate\Console\OutputStyle;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\Storage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\Repository\Contracts\MetricAggregationRepository;

final readonly class PruneMetricsTask
{
    private MetricAggregationRepository $repository;
    private MetricStorage $realtime;

    private function __construct(private Aggregation $aggregation, private Storage $storage, private ?OutputStyle $output = null)
    {
        $this->repository = app(MetricAggregationRepository::class);
        $this->realtime = app(MetricStorage::class);
    }

    public function __invoke(): void
    {
        $deleted = 0;

        if ($this->storage === Storage::Persistent) {
            $before = now()->sub($this->aggregation->retention());
            $this->output?->info(sprintf('Pruning metrics older than %s', $before->toDateTimeString()));

            $deleted = $this->repository->prune($this->aggregation);
        }

        if ($this->storage === Storage::Realtime) {
            $before = now()->sub($this->aggregation->retention());
            $this->output?->info(sprintf('Pruning metrics older than %s', $before->toDateTimeString()));

            $deleted = $this->realtime->prune($this->aggregation, $before);
        }

        $this->output?->info(sprintf('Pruned %d metrics from %s storage', $deleted, $this->storage->value));
    }

    public static function with(Aggregation $aggregation, Storage $storage, ?OutputStyle $output = null): self
    {
        if (app()->runningInConsole()) {
            return new self($aggregation, $storage, $output);
        }

        return new self($aggregation, $storage);
    }
}
