<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\DTO\RiskFactor;

interface RuleInterface
{
    public function description(): ?string;
    public function enabled(): bool;

    /**
     * @return Collection<RiskFactor>
     */
    public function evaluate(): Collection;
    public function name(): string;
    public function weight(): float;

    /**
     * @return array<float>
     */
    public function thresholds(): array;
}
