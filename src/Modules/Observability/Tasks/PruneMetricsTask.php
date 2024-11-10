<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Illuminate\Console\OutputStyle;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;

final readonly class PruneMetricsTask
{
    private MetricAggregationRepository $repository;

    private function __construct(private Aggregation $aggregation, private ?OutputStyle $output = null)
    {
        $this->repository = app(MetricAggregationRepository::class);
    }

    public function __invoke(): void
    {
        $before = now()->sub($this->aggregation->retention());
        $this->output?->info(sprintf('Pruning metrics older than %s', $before->toDateTimeString()));

        $deleted = $this->repository->prune($this->aggregation);
        $this->output?->info(sprintf('Pruned %d metrics', $deleted));
    }

    public static function with(Aggregation $aggregation, ?OutputStyle $output = null): self
    {
        if (app()->runningInConsole()) {
            return new self($aggregation, $output);
        }

        return new self($aggregation);
    }
}
