<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Illuminate\Console\Concerns\InteractsWithIO;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;

final readonly class PruneMetricsTask
{
    use InteractsWithIO;

    private MetricAggregationRepository $repository;

    private function __construct(private Aggregation $aggregation)
    {
        $this->repository = app(MetricAggregationRepository::class);
    }

    public function __invoke(): void
    {
        $before = now()->sub($this->aggregation->retention());
        $this->output?->writeln(sprintf('Pruning metrics older than %s', $before->toDateTimeString()));

        $deleted = $this->repository->prune($this->aggregation);
        $this->output?->writeln(sprintf('Pruned %d metrics', $deleted));
    }

    public static function with(Aggregation $aggregation): self
    {
        return new self($aggregation);
    }
}
