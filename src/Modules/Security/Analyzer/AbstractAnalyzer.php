<?php

namespace Ninja\DeviceTracker\Modules\Security\Analyzer;

use Ninja\DeviceTracker\Modules\Security\Contracts\BehaviorAnalyzer;
use Ninja\DeviceTracker\Modules\Security\Contracts\PatternRepository;

abstract class AbstractAnalyzer implements BehaviorAnalyzer
{
    public function __construct(protected readonly PatternRepository $repository)
    {
    }

    protected function deviation(array $values): float
    {
        $average = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($x) => ($x - $average) ** 2, $values)) / count($values);

        return sqrt($variance);
    }
}
