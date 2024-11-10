<?php

namespace Ninja\DeviceTracker\Modules\Observability\Tasks;

use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricAggregationRepository;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class PruneMetricsTask
{
    private MetricAggregationRepository $repository;

    private function __construct(private Aggregation $aggregation, private ?OutputInterface $output = null)
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
        if (app()->runningInConsole()) {
            return new self($aggregation, app(OutputInterface::class));
        }

        return new self($aggregation);
    }
}
